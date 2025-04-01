PokerHost

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


