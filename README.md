# ADISE26_2022092
This is an RESTful API that serves a Quoridor game where two human players can play with each other

All the end points must include the `X-Token` Header with the token every user receives

## API ENDPOINTS
| Method | URL | Payload / Body | Description |
| :--- | :--- | :--- | :--- |
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
| `GET` | `/api/matchhistory` | — | Get match history |
| `GET` | `/api/matchhistory/{userid}` | — | Get User match history |

# WALL PLACEMENT

When a wall is placed either vertically or horizontally, the starting point is the coordinates provided and then the wall is placed incrementally on the axis provided.

For example, a wall placement of `"e4h"`, will place a wall on the squares e4 and f4 between the e4 and e5 squares(horizontally, increments in x axis aka the letters axis). 
In the same way when the placement provided is `"e4v"` the wall will be placed on the squares e4 and e5 on the right side of the e4 square.

### Example: `e4h` vs `e4v`

```text
         col 4 (e)     col 5 (f)
       +-------------+-------------+
row 4  |     e5      |     f5      |
       +=============+=============+  <-- "e4h" blocks e5<->e4 and f5<->f4
row 5  |    [e4]     |     f4      |
       +-------------+-------------+

         col 4 (e)     col 5 (f)
       +-------------+-------------+
row 4  |     e5     ||     f5      |
       +------------||-------------+  <-- "e4v" blocks e5<->f5 and e4<->f4
row 5  |    [e4]    ||     f4      |
       +------------++-------------+
```

