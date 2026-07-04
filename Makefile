COMPOSER ?= composer
PHPUNIT   = vendor/bin/phpunit
VERSION  ?=
COVERAGE_DIR ?= coverage
MIN_COVERAGE ?= 0

.DEFAULT_GOAL := help

.PHONY: help install test test-unit test-integration coverage coverage-check analyse cs-check cs-fix check publish clean

help: ## Show this help
	@grep -hE '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

install: ## Install dev dependencies
	$(COMPOSER) install

## --- test endpoints ---

test: ## Run the full test suite (unit + integration)
	$(COMPOSER) test

test-unit: ## Run unit tests only
	$(PHPUNIT) --testsuite Unit

test-integration: ## Run integration tests only (needs redis/memcached)
	$(PHPUNIT) --testsuite Integration

coverage: ## Run tests with coverage: text summary + HTML + Clover (needs pcov or xdebug)
	XDEBUG_MODE=coverage $(PHPUNIT) \
		--coverage-text \
		--coverage-html $(COVERAGE_DIR)/html \
		--coverage-clover $(COVERAGE_DIR)/clover.xml
	@echo "HTML report: $(COVERAGE_DIR)/html/index.html   Clover: $(COVERAGE_DIR)/clover.xml"

coverage-check: coverage ## Fail if line coverage < MIN_COVERAGE, e.g. make coverage-check MIN_COVERAGE=80
	@php -r '$$m = simplexml_load_file("$(COVERAGE_DIR)/clover.xml")->project->metrics; \
		$$s = (int) $$m["statements"]; $$c = (int) $$m["coveredstatements"]; \
		$$p = $$s ? $$c * 100 / $$s : 100.0; $$min = (float) "$(MIN_COVERAGE)"; \
		printf("Line coverage: %.2f%% (%d/%d lines), minimum %.2f%%\n", $$p, $$c, $$s, $$min); \
		if ($$p + 1e-9 < $$min) { fwrite(STDERR, "FAIL: coverage below minimum\n"); exit(1); } \
		echo "OK\n";'

analyse: ## Run PHPStan static analysis
	$(COMPOSER) analyse

cs-check: ## Check code style without modifying files
	$(COMPOSER) cs-check

cs-fix: ## Apply code style fixes
	$(COMPOSER) cs-fix

check: cs-check analyse test ## Run style, static analysis, and tests

## --- publish endpoints ---

publish: ## Tag and push a release: make publish VERSION=x.y.z (Packagist auto-updates)
	@test -n "$(VERSION)" || { echo "VERSION is required, e.g. make publish VERSION=0.3.0"; exit 1; }
	@echo "$(VERSION)" | grep -Eq '^[0-9]+(\.[0-9]+)+$$' || { echo "VERSION must look like 0.3.0"; exit 1; }
	@git rev-parse -q --verify "refs/tags/$(VERSION)" >/dev/null && { echo "Tag $(VERSION) already exists."; exit 1; } || true
	@git diff --quiet && git diff --cached --quiet || { echo "Working tree is dirty; commit or stash first."; exit 1; }
	$(COMPOSER) validate --strict
	$(MAKE) check
	git tag -a $(VERSION) -m "Release $(VERSION)"
	git push origin $(VERSION)
	@echo "Pushed tag $(VERSION). Packagist will update via its GitHub webhook."

clean: ## Remove caches, coverage output, and installed dependencies
	rm -rf vendor .phpunit.cache .php-cs-fixer.cache $(COVERAGE_DIR)
