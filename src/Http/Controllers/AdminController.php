<?php

namespace Whilesmart\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
    public function offers(OfferRegistry $offers): JsonResponse
    {
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

    public function createOffer(CreateOfferRequest $request, OfferRegistry $offers, string $provider): JsonResponse
    {
        $offer = $this->offerProvider($offers, $provider)->create($request->validated()['attributes']);

        return response()->json(['success' => true, 'data' => $offer->toArray()], 201);
    }

    public function revokeOffer(OfferRegistry $offers, string $provider, string $id): JsonResponse
    {
        $this->offerProvider($offers, $provider)->revoke($id);

        return response()->json(['success' => true]);
    }

    private function offerProvider(OfferRegistry $offers, string $key): OfferProvider
    {
        $provider = $offers->get($key);

        abort_if($provider === null, 404, 'No offer provider is registered under that key.');

        return $provider;
    }

    public function metrics(Request $request, EngagementManager $engagement, ClientRegistry $clients): JsonResponse
    {
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
        $resource = config('admin.resources.user', AdminUserResource::class);
        $users = $provider->paginate((string) $request->input('q', ''), (int) $request->input('per_page', 25));

        return response()->json(['success' => true, 'data' => $resource::collection($users)->response()->getData(true)]);
    }

    public function user(mixed $id, AdminUserProvider $provider): JsonResponse
    {
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
