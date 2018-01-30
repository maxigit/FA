.PHONY: dumps/fa/db.sql output/restore_db.log

dumps/fa/db.sql:
	mysqldump -ufa_test -p --add-drop-table  --dump-date --opt --skip-extended-insert fa_test > $@

dump: dumps/fa/db.sql
	(cd ../data; git add $<)


restore_db: output/restore_db.log

output/restore_db.log :
	mysql -ufa_test fa_test < dumps/fa/db.sql 
	touch $@ # just to know last time we did it

build_fa:
	docker build -t apache Docker

run_fa:
	docker run --name fa -p 80:80 -v /root/prod/fa:/var/www/html -v /root/prod/data:/var/www/data -v /root:/host --net=host -d apache
run_fa_bash:
	docker run -it --rm -p 80:80 -v /root/prod/fa:/var/www/html -v /root/prod/data:/var/www/data -v /root:/host --net=host apache bash
run_mysql:
	docker run --name mysql -e MYSQL_ROOT_PASSWORD=mu -v /root/prod/var/mysql:/var/lib/mysql --net=host -p 3306:3306 -d mariadb

clear_js_cache:
	rm -rf ../fa-config/company/0/js_cache/*
