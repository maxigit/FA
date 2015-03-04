#!/usr/bin bash
# syncronize for fadev and merge (with a rebase)

git fetch fadev
git checkout -b _to_rebase fadev/master  &&\
git rebase -i --onto master last_merge     &&\
git checkout master &&\
git merge --no-ff _to_rebase &&\
git tag -f last_merge fadev/master &&\
git branch -D _to_rebase
