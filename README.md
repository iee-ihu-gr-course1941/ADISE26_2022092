# ADISE26_2022092
This is an RESTful API that serves a Quoridor game where two human players can play with each other

All the end points must include the `X-Token` Header with the token every user receives

## API ENDPOINTS
| Method | URL | Payload / Body | Description |
| :--- | :--- | :--- | :--- |
| `GET` | `/` | — | OAuth 2.0 login & session/token diagnostics |
| `GET` | `/api/users` | — | Get leaderboard / all users |
| `GET` | `/api/users/{userid}` | — | Get user profile & wins |
| `DELETE` | `/api/users/{id}` | — | Delete authenticated user |
| `GET` | `/api/games` | — | Get joinable (`waiting`) games |
| `POST` | `/api/games` | `{"color":"r"}` | Create a new game (`"r"` or `"b"`) |
| `GET` | `/api/games/{id}` | — | Get a single game |
| `PATCH` | `/api/games/{id}` | — | Join a waiting game |
| `DELETE` | `/api/games/{id}` | — | Cancel/delete own waiting game |
| `POST` | `/api/play` | `{"move":"e2"}` | Play a pawn move (`"e2"`) or wall (`"e4h"`, `"e2v"`) |
| `PATCH` | `/api/play/{id}` | — | Forfeit active game |
| `GET` | `/api/matchhistory` | — | Get finished games history |

