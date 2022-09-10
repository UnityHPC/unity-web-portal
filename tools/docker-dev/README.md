# Unity Web Portal Dev Environment

## Environment Setup

1. Download and install [docker desktop](https://www.docker.com/products/docker-desktop/) for your appropriate OS.
1. Clone this repository: `git clone https://github.com/UMass-RC/unity-web-portal-docker.git && cd unity-web-portal-docker`
1. Clone the web portal: `git clone -b <VERSION> https://github.com/UMass-RC/unity-web-portal.git`
    1. Set up the web portal following the readme [here](https://github.com/UMass-RC/unity-web-portal).
        ```
        LDAP Details for this Environment:
        Host: identity
        Username: cn=admin,dc=unity,dc=rc,dc=umass,dc=edu
        Password: password

        SQL Details for this Environment:
        Host: sql
        Username: unity
        Password: password
        DBName: unity
        ```
1. Run the build script: `./build.sh`
1. Run the environment: `./run.sh`
    1. You will then see an output of details on how to access the dev portal, and you can live edit files in the repo cloned in step (2) and they will take changes in your browser.