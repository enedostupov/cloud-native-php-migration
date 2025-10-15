# Cloud-Native PHP Migration Demo

Demonstration of migrating a legacy PHP application to a cloud-native architecture with a Node.js API gateway.

## Architecture
Client → Node Gateway (3000) → PHP API (8081) → MySQL (3306)

- **PHP Backend**: REST API with CRUD operations
- **Node Gateway**: TypeScript proxy with JWT auth and rate limiting
- **Database**: MySQL 8.0
- **Infrastructure**: Docker Compose + Kubernetes (Helm)

## Requirements

- Docker & Docker Compose
- Make (optional)
- Helm 3 (for Kubernetes deployment)
- Minikube (for local Kubernetes testing)


## Deploy
```bash
docker compose build

minikube image load cloud-native-php-migration-php-app:latest
minikube image load cloud-native-php-migration-node-gateway:latest

helm install cloud-demo helm/

kubectl get pods
kubectl get svc
```

## Access
```bash
kubectl port-forward svc/node-gateway 3000:3000

curl http://localhost:3000/health
```

## Testing
### Unit Tests
```bash
make test
```

### Authentication Tests
```bash
./node-gateway/src/test-with-auth.sh
```

## Authentication
The gateway requires JWT tokens for API access.

### Login
```bash
curl -X POST http://localhost:3000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin"}'
```
Response:
```json
{
  "token": "eyJhbGci...",
  "expiresIn": 3600
}
```
### Using the token
```bash
TOKEN="your-token-here"

# List items
curl http://localhost:3000/api/items \
  -H "Authorization: Bearer $TOKEN"

# Create item
curl -X POST http://localhost:3000/api/items \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Book Title","author":"Author Name"}'
```

## API Endpoints
### Public
- `GET /health` - Health check
- `POST /auth/login` - Get JWT token

### Protected (requires Bearer token)
- `GET /api/items` - List all items
- `POST /api/items` - Create item
- `GET /api/items/{id}` - Get item
- `PUT /api/items/{id}` - Update item
- `DELETE /api/items/{id}` - Delete item

## Security Features
JWT Authentication
Token expires after 1 hour
Configurable secret via JWT_SECRET environment variable

## Rate Limiting
100 requests per minute per IP
Returns 429 status when exceeded

## Available Commands
```bash
make dev           # Start services
make test          # Run PHPUnit tests
make logs          # View PHP logs
make logs-gateway  # View gateway logs
make shell         # PHP container shell
make shell-gateway # Gateway container shell
make stop          # Stop services
make clean         # Remove everything
make rebuild       # Rebuild from scratch
make seed          # Add sample data
make status        # Container status
```
