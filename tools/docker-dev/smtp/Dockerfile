FROM ubuntu:20.04

ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y \
    postfix

EXPOSE 25

CMD ["smtp-sink", "-h", "unity-smtp-dev", "-D", "/tmp/sink-mail.log", "25"]