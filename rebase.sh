#!/usr/bin bash
# syncronize for hg and merge (with a rebase)

(cd ../hgfa&& hg pull&& hg bookmark -r default -f master && hg gexport)
git fetch hg
git checkout -b _to_rebase hg/master  &&\
git rebase -i --onto master hg-last     &&\
git checkout master &&\
git merge --no-ff _to_rebase &&\
git tag -f hg-last hg/master &&\
git branch -D _to_rebase
