# Contributing

When contributing to this repository, please first discuss the change you wish to make via issue,
email, or any other method with the owners of this repository before making a change.

Please note we have a [code of conduct](https://github.com/nicumicle/simple-jwt-login/blob/master/CODE_OF_CONDUCT.md), please follow it in all your interactions with the project.

## Prepare local environment

### Requirements
- git
- docker
- docker-compose

### Init local environment

1. Clone this repository
2. Init docker containers 
```
   docker-composer -f docker\docker-compose.yaml up
```
3. Access `http://localhost:88/wp-admin` in your browser and set up WordPress 


### Running tests on local environment

1. Connect to docker container
```
   docker exec -it wordpress /bin/bash
```
2. Go to the dev folder
```
   cd /var/www/dev
```
3. Run the Unit tests
```
   vendor/bin/phpunit --testsuite "Unit" --coverage-text
```

3. Run the Feature tests
```
   vendor/bin/phpunit --testsuite "Feature" --coverage-text
```

4. Check plugin. This script will do all the checks for the plugin. See `composer.json` -> scripts for more details
```
    composer check-plugin
```


## Pull Request Process

1. Ensure any install or build dependencies are removed.
2. Write tests for the new added code
3. Run `composer check-plugin` on your local docker and make sure all checks pass







