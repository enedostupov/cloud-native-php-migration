.PHONY: dev build test test-clean logs logs-gateway shell shell-gateway stop clean rebuild seed check-health status

# Start dev environment
dev:
	docker compose up --build -d

# Build images without starting
build:
	docker compose build

# Run tests
test:
	docker compose exec php-app composer test

# Clean database
test-clean:
	docker compose exec php-app php -r '\
		require "src/db.php"; \
		db()->exec("TRUNCATE TABLE books"); \
		echo "✓ Test data cleaned\n";'

# View PHP logs
logs:
	docker compose logs -f php-app

# View Node Gateway logs
logs-gateway:
	docker compose logs -f node-gateway

# Open shell in PHP container
shell:
	docker compose exec php-app bash

# Open shell in Gateway container
shell-gateway:
	docker compose exec node-gateway sh

# Stop containers
stop:
	docker compose stop

# Clean everything
clean:
	docker compose down -v
	rm -rf php-app/vendor

# Rebuild from scratch
rebuild:
	docker compose down -v
	docker compose build --no-cache
	docker compose up -d

# Add test data
seed:
	docker compose exec php-app php -r '\
		require "src/db.php"; \
		$$books = [ \
			["title" => "Book_1", "author" => "Author_1"], \
			["title" => "Book_2", "author" => "Author_2"], \
			["title" => "Book_3", "author" => "Author_3"] \
		]; \
		$$stmt = db()->prepare("INSERT INTO books(title, author) VALUES (?, ?)"); \
		foreach ($$books as $$book) { \
			$$stmt->execute([$$book["title"], $$book["author"]]); \
			echo "✓ Created: {$$book["title"]}\n"; \
		}'

# Check gateway and backend health
check-health:
	@echo "Checking service health..."
	@curl -s http://localhost:3000/health | jq . || curl -s http://localhost:3000/health

# Show container status
status:
	docker compose ps
