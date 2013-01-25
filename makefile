.PHONY: dumps/fa/db.sql output/restore_db.log

dumps/fa/db.sql:
	mysqldump -ufa_test -p --add-drop-table  --dump-date --opt --skip-extended-insert fa_test > $@

dump: dumps/fa/db.sql
	(cd ../data; git add $<)


restore_db: output/restore_db.log

output/restore_db.log :
	mysql -ufa_test fa_test < dumps/fa/db.sql 
	touch $@ # just to know last time we did it

