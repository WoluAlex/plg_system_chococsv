# System - Chococsv

[![Maintainability](https://api.codeclimate.com/v1/badges/ed731095c94fa2a500a8/maintainability)](https://codeclimate.com/github/WoluAlex/plg_system_chococsv/maintainability)

### Requirements:
- PHP 8.1 minimum
- Basic knowledge of command line
- Install a web server like https://bearsampp.com/ or Docker Desktop https://www.docker.com/products/docker-desktop/
- Install latest Joomla 4 and latest Joomla 5
- Install latest release of plg_system_chococsv extension on both Joomla versions

### Usage with Docker Desktop

1. Clone this repo  ` git clone https://github.com/WoluAlex/plg_system_chococsv `

2. Go into the clone repo by typing this command in your terminal ` cd plg_system_chococsv `

3. Execute this command in your terminal to launch Joomla 4 and Joomla 5 sites with Docker Desktop

```

docker compose up -d

```

4. Follow instructions for Joomla 4 go to ` http://127.0.0.1:54490 `
5. Follow instructions for Joomla 5 go to ` http://127.0.0.1:54590 `

NOTE: When configuring Joomla, your database host should be set as `db`.

### SPECIAL THANKS:
- Marc DECHÈVRE (@woluweb)
- All other Super Joomlers
