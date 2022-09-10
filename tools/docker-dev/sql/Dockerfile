FROM ubuntu:20.04

ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y \
    mariadb-server \
    mariadb-client
RUN sed -i '/bind-address/c\bind-address = 0.0.0.0' /etc/mysql/mariadb.conf.d/50-server.cnf
COPY sql-init.sql /tmp/sql-init.sql

RUN service mysql start; \
    mariadb -e "CREATE DATABASE unity"; \
    mariadb -e "CREATE USER 'unity'@'%' IDENTIFIED BY 'password'"; \
    mariadb -e "GRANT ALL PRIVILEGES ON unity.* TO 'unity'@'%'"; \
    mariadb -e "FLUSH PRIVILEGES"; \
    mariadb unity < /tmp/sql-init.sql

RUN rm -rf /tmp/sql-init.sql

EXPOSE 3306

CMD ["mysqld"]