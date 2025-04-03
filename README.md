# PokerHost

## Docker hosting:
To create image files for running containers from within /laradock-pokerhost:
```docker compose up -d postgres caddy redis```
To go inside your workspace container: 
```docker compose exec workspace bash```
To go into a specific container: (workspace-container-id can be obtained with docker ps) 
```docker exec -it {workspace-container-id} bash```

## DEV:
Since it's using a submodule, the changes need to be commited inside the laradock-pokerhost directory first, and then inside the main app.
```
cd laradock
git add .
git commit -m "Customized Docker configuration"
git push origin main

cd ..
git add laradock
git commit -m "Updated Laradock submodule"
git push
```

If hosting locally, in order for caddy to work, your OS needs to know the virtual host address and name:
For windows you have to modify the C:\Windows\System32\drivers\etc\hosts file to include "127.0.0.1 laradock.test"
