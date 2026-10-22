<?php

namespace Whilesmart\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Whilesmart\Admin\Contracts\AdminUserProvider;
use Whilesmart\Admin\Contracts\OfferProvider;
use Whilesmart\Admin\Http\Requests\CreateOfferRequest;
use Whilesmart\Admin\Http\Resources\AdminUserResource;
use Whilesmart\Admin\Mail\TemplateMail;
use Whilesmart\Admin\Models\MailTemplate;
use Whilesmart\Admin\Support\OfferRegistry;
use Whilesmart\Admin\Support\TemplateRegistry;
use Whilesmart\Engagement\EngagementManager;
use Whilesmart\Engagement\Support\ClientRegistry;
use Whilesmart\Engagement\Support\Period;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

class AdminController extends Controller
{
    /**
     * Every registered provider, its fields, and its offers.
     *
     * A host with none registered gets an empty list, not an error.
     */
    public function offers(Request $request, OfferRegistry $offers): JsonResponse
    {
        $this->authorizeConsole($request);

        return response()->json([
            'success' => true,
            'data' => array_values(array_map(fn ($provider) => [
                'key' => $provider->key(),
                'label' => $provider->label(),
                'fields' => array_map(fn ($field) => $field->toArray(), $provider->fields()),
                'offers' => array_map(fn ($offer) => $offer->toArray(), $provider->all()),
            ], $offers->all())),
        ]);
    }

    public function createOffer(Request $request, OfferRegistry $offers, string $provider): JsonResponse
    {
        $this->authorizeConsole($request);

        // Defaulted rather than read straight out: a host that published this
        // config before the key existed has an array that wins over the one
        // shipped here, and would resolve nothing.
        $requestClass = config('admin.requests.create_offer', CreateOfferRequest::class);
        $validated = app($requestClass)->validated();
        $offer = $this->offerProvider($offers, $provider)->create($validated['attributes']);

        return response()->json(['success' => true, 'data' => $offer->toArray()], 201);
    }

    public function revokeOffer(Request $request, OfferRegistry $offers, string $provider, string $id): JsonResponse
    {
        $this->authorizeConsole($request);

        $this->offerProvider($offers, $provider)->revoke($id);

        return response()->json(['success' => true]);
    }

    /**
     * The console answers to the configured owner, the way a mail template
     * answers to its own. Checked in the controller rather than in a request
     * class, so a host swapping that class cannot drop it.
     */
    private function authorizeConsole(Request $request): void
    {
        $owner = config('admin.owner');

        abort_unless(app(OwnerAuthorizer::class)->authorize(
            $request->user(),
            $owner['type'],
            $owner['id'],
        ), 403);
    }

    private function offerProvider(OfferRegistry $offers, string $key): OfferProvider
    {
        $provider = $offers->get($key);

        abort_if($provider === null, 404, 'No offer provider is registered under that key.');

        return $provider;
    }

    /**
     * The pages whose backend is optional, and whether this host serves them, so
     * the console offers only what will load. Feedback comes from its own
     * package and is served once that package's routes sit under this prefix.
     */
    public function features(Request $request): JsonResponse
    {
        $this->authorizeConsole($request);

        $feedback = trim((string) config('admin.route_prefix', 'api/admin'), '/').'/feedback';

        return response()->json([
            'success' => true,
            'data' => [
                'feedback' => collect(Route::getRoutes()->getRoutes())
                    ->contains(fn ($route) => $route->uri() === $feedback && in_array('GET', $route->methods(), true)),
                'posthog' => $this->sharedDashboard() !== null,
            ],
        ]);
    }

    /**
     * Only a shared dashboard's https address is handed out: the console puts it
     * in a frame, and anything else would load a script address, an insecure
     * page, or a PostHog page that needs signing in there. The host is not
     * checked, so a self-hosted PostHog works too.
     */
    public function posthog(Request $request): JsonResponse
    {
        $this->authorizeConsole($request);

        return response()->json([
            'success' => true,
            'data' => ['dashboard_url' => $this->sharedDashboard()],
        ]);
    }

    private function sharedDashboard(): ?string
    {
        $url = config('admin.posthog.dashboard_url');

        return is_string($url)
            && filter_var($url, FILTER_VALIDATE_URL) !== false
            && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https'
            && str_starts_with((string) parse_url($url, PHP_URL_PATH), '/embedded/')
            ? $url
            : null;
    }

    public function metrics(Request $request, EngagementManager $engagement, ClientRegistry $clients): JsonResponse
    {
        $this->authorizeConsole($request);

        $granularity = in_array($request->query('granularity'), ['day', 'week', 'month'], true)
            ? $request->query('granularity')
            : 'day';
        $days = min(366, max(1, (int) $request->query('days', 30)));
        $clientKey = $request->string('client')->toString();
        $clientKey = $clientKey !== '' && $clients->has($clientKey) ? $clientKey : null;

        return response()->json([
            'success' => true,
            'data' => $engagement->report(Period::lastDays($days, $granularity, $clientKey)),
        ]);
    }

    public function users(Request $request, AdminUserProvider $provider): JsonResponse
    {
        $this->authorizeConsole($request);

        $resource = config('admin.resources.user', AdminUserResource::class);
        $users = $provider->paginate((string) $request->input('q', ''), (int) $request->input('per_page', 25));

        return response()->json(['success' => true, 'data' => $resource::collection($users)->response()->getData(true)]);
    }

    public function user(Request $request, mixed $id, AdminUserProvider $provider): JsonResponse
    {
        $this->authorizeConsole($request);

        $resource = config('admin.resources.user', AdminUserResource::class);
        $user = $provider->find($id);

        abort_unless($user, 404);

        return response()->json(['success' => true, 'data' => new $resource($user)]);
    }

    public function templates(Request $request, TemplateRegistry $registry): JsonResponse
    {
        $templates = collect($registry->all())->filter(fn (array $template) => app(OwnerAuthorizer::class)->authorize(
            $request->user(),
            $template['owner_type'],
            $template['owner_id'],
        ))->values();

        return response()->json(['success' => true, 'data' => $templates]);
    }

    public function template(string $key, Request $request, TemplateRegistry $registry): JsonResponse
    {
        $template = $registry->findOrCreate($key);
        $this->authorizeTemplate($template, $request);

        return response()->json(['success' => true, 'data' => $registry->serialize($template)]);
    }

    public function updateTemplate(string $key, Request $request, TemplateRegistry $registry): JsonResponse
    {
        $requestClass = config('admin.requests.update_mail_template');
        $validated = app($requestClass)->validated();
        $template = $registry->findOrCreate($key);
        $template->update($validated);

        return response()->json(['success' => true, 'data' => $registry->serialize($template->fresh())]);
    }

    public function previewTemplate(string $key, Request $request, TemplateRegistry $registry, AdminUserProvider $users): JsonResponse
    {
        $template = $registry->findOrCreate($key);
        $this->authorizeTemplate($template, $request);
        $recipient = $request->user() ?? $users->find($request->input('recipient_id'));

        $rendered = $registry->render($request->all(), $recipient);
        $rendered['html'] = (new TemplateMail(
            $rendered['subject'],
            $rendered['body'],
            $rendered['cta_label'],
            $rendered['cta_url'],
        ))->render();

        return response()->json(['success' => true, 'data' => $rendered]);
    }

    private function authorizeTemplate(MailTemplate $template, Request $request): void
    {
        abort_unless(app(OwnerAuthorizer::class)->authorize(
            $request->user(),
            $template->owner_type,
            $template->owner_id,
        ), 403);
    }
}
