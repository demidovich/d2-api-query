FROM demidovich/postgres:12.4-debian

ARG UID=1000
ARG GID=1000
ENV UID=${UID:-1000} \
    GID=${GID:-1000}

RUN useradd -u ${UID} d2

USER $UID

EXPOSE 5432
