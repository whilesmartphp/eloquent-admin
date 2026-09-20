-include package.mk

bootstrap:
	@[ -f package.mk ] || docker compose run --rm app cp /usr/local/share/package.mk /app/package.mk
