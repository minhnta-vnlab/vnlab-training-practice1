### VNLab Training - Pactice - Login Website with 2FA
## Environments
Make `.env` with content like the example below:
```env
POSTGRES_DB=<postgres database>
POSTGRES_USER=<postgres username>
POSTGRES_PASSWORD=<postgres password>
```
## Run by Docker
Compose everything into Docker
```
docker compose up -d
```
The website will be running at: http://127.0.0.1

The domains is setted up in Nginx for both frontend and backend:
- Frontend: y2aa-frontend.test
- Backend: y2aa-backend.test

**For Development, please add two DNS records for these two domains**
