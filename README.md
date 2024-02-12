Blocks Edit
===========
Code for the Blocks Edit frame and the site https://app.blocksedit.com.

* [Requirements](#requirements)
* [Installing](#installing)
* [Local Development](#local-development)
* [Docker](#docker)
* [Running Tests](#running-tests)
* [Framework](#framework)
    * [Controllers](#controllers)
    * [Repositories](#repositories)
    * [Entities](#entities)
    * [Views](#views)
    * [Middleware](#middleware)
    * [Commands](#commands)
    * [CSS](#css)

## Requirements
* Ubuntu 18
* Nginx or Apache
* Redis
* PHP 7.2
* Composer
* Yarn with NPM 13
* Git

## Installing
Install the requirements according to the vendor docs. See the docs for an example [Nginx configuration](docs/nginx.md).

These instructions assume you're logged in as the user "ubuntu" which needs to be added to the "www-data" group.

```
sudo usermod -a -G www-data ubuntu
```

Run the following commands as the "ubuntu" user.

```
cd ~
git clone git@github.com:blocksedit/blocksedit.git app.blocksedit.com

sudo su
mv app.blocksedit.com /var/www
cd /var/www/app.blocksedit.com

composer install
yarn install
yarn run build
bin/version.sh

mkdir public/invoices
mkdir public/tmp-uploads

sudo chown -R www-data:www-data /var/www/app.blocksedit.com
sudo chmod -R g+w /var/www/app.blocksedit.com
exit
```

Create the database table and import the data.

```
mysql < data.sql
```

Copy the sample configuration to `config/config.yaml` and then edit the configuration values including the details for the database that was just created.

```
cp config/config-sample.yaml config/config.yaml
```

Finally, build the container and configuration.

```
bin/console cache:clear
```

## Local Development
You will need dnsmasq in order to run BE locally.

Ubuntu 18.04+ comes with systemd-resolve which you need to disable since it binds to port 53 which will conflict with Dnsmasq port.
Run the following commands to disable the resolved service:

```bash
sudo systemctl disable systemd-resolved
sudo systemctl stop systemd-resolved
```
Also, remove the symlinked resolv.conf file

```bash
ls -lh /etc/resolv.conf
sudo unlink /etc/resolv.conf
echo "nameserver 8.8.8.8" | sudo tee /etc/resolv.conf
```

Dnsmasq is available on the apt repository, easy installation can be done by running:

```bash
sudo apt update
sudo apt install dnsmasq
```

The main configuration file for Dnsmasq is /etc/dnsmasq.conf. Configure Dnsmasq by modifying this file.

```bash
sudo vim /etc/dnsmasq.conf
```

Here is minimal configuration

```
# Listen on this specific port instead of the standard DNS port
# (53). Setting this to zero completely disables DNS function,
# leaving only DHCP and/or TFTP.
port=53

# Never forward plain names (without a dot or domain part)
domain-needed

# Never forward addresses in the non-routed address spaces.
bogus-priv

# By  default,  dnsmasq  will  send queries to any of the upstream
# servers it knows about and tries to favour servers to are  known
# to  be  up.  Uncommenting this forces dnsmasq to try each query
# with  each  server  strictly  in  the  order  they   appear   in
# /etc/resolv.conf
strict-order

# Set this (and domain: see below) if you want to have a domain
# automatically added to simple names in a hosts-file.
expand-hosts

# Add other name servers here, with domain specs if they are for
# non-public domains.
#server=/localnet/192.168.0.1
server=8.8.8.8
server=8.8.4.4

# Add domains which you want to force to an IP address here.
# The example below send any host in double-click.net to a local
# web-server.
address=/dev.blocksedit.com/127.0.0.1
```

## Docker
The following commands can be used to build and run the Docker container.

```bash
yarn run docker:build
yarn run docker:run
```
Or use docker compose to start all the required services.

```bash
docker-compose up -d
```

#### AWS Configuration
In order to push/pull from the private Docker registry you will need to login with AWS. You may need to install
the AWS CLI tools and configure them with your AWS credentials. You will need a copy of the AWS access key and secret for the Blocks Edit AWS account.

```bash
sudo apt install awscli
aws configure
```

Next, you need Docker to be able to push images to your ECR repository. The docker login command will allow you to do this, so using the AWS CLI, retrieve your ECR password and pipe it into the Docker command:

```bash
aws ecr get-login-password --region <account-region> \
| docker login \
    --username AWS \
    --password-stdin 783209329702.dkr.ecr.us-east-2.amazonaws.com/blocksedit
```

#### Importing Data
Import the data into mysql if you haven't already. First, obtain a copy of the database dump file from the production server. Then, find the container ID for the mysql container.

```bash
docker ps
```

Which shows something like this:

```bash
CONTAINER ID   IMAGE                                                            COMMAND                  CREATED          STATUS          PORTS                                                  NAMES
4e82719b6b69   783209329702.dkr.ecr.us-east-2.amazonaws.com/blocksedit:0.0.1    "/entrypoint.sh"         19 minutes ago   Up 19 minutes   0.0.0.0:9109->80/tcp, :::9109->80/tcp                  blocksedit_web_1
b0bfb5a30d3d   redis:alpine                                                     "docker-entrypoint.s…"   19 minutes ago   Up 19 minutes   0.0.0.0:9379->6379/tcp, :::9379->6379/tcp              blocksedit_redis_1
ee6c5d0fb4d4   783209329702.dkr.ecr.us-east-2.amazonaws.com/cert-server:0.0.1   "/entrypoint.sh"         19 minutes ago   Up 19 minutes   0.0.0.0:9119->80/tcp, :::9119->80/tcp                  blocksedit_certs_1
c0cf21329b40   mysql                                                            "docker-entrypoint.s…"   19 minutes ago   Up 19 minutes   33060/tcp, 0.0.0.0:9306->3306/tcp, :::9306->3306/tcp   blocksedit_db_1

```
In the above example, the container ID for the mysql container is `c0cf21329b40`.

Then, import the data.

```bash
docker exec -i <container_id> mysql -ublocksedit -pb0N7m48Z1CR3Vr blocksedit_dev < blocksedit.sql
```

#### Site Configuration
You will need to modify the `config/config.yaml` file to use the values in the `docker-compose.yml` file.
Redis will be running on port `9379` and MySQL on port `9306`. The MySQL security settings are:

* MYSQL_ROOT_PASSWORD: b0N7m48Z1CR3Vr
* MYSQL_DATABASE: blocksedit_dev
* MYSQL_USER: blocksedit
* MYSQL_PASSWORD: b0N7m48Z1CR3Vr

## Running Tests

```
composer run tests
```

## Crons
As the "ubuntu" user add the following to the crontab using the command `crontab -e`.

```
@hourly /var/www/app.blocksedit.com/bin/console billing:payments:exec
@daily /var/www/app.blocksedit.com/bin/console billing:send:notices
@hourly /var/www/app.blocksedit.com/bin/console email:bounces
@hourly /var/www/app.blocksedit.com/bin/console email:daily:send
* * * * * run-one /var/www/app.blocksedit.com/bin/console layouts:upgrade
* * * * * run-one /var/www/app.blocksedit.com/bin/console section:library:thumbnails
@daily /var/www/app.blocksedit.com/bin/console cdn:clean
```

## Framework
Blocks Edit uses a custom MVC framework that grew organically from a simple library written by a long gone developer. See [the first commit](https://github.com/ovidem/blocksedit/tree/6c618871c46a6e5cd74ae0510edb86c603b13acd). It's been largely influenced by the [Symfony framework](https://symfony.com/).

The framework library classes are found in `{projectDir}/src/BlocksEdit`.

### Controllers
Endpoints are handled by controller classes which define one or more action methods. Controllers can be found in the directory `{projectDir}/src/Controllers` and use the `Controllers` base namespace.

The following example controller walks through an initial version with further refactoring to simplify the code.

#### First Version
The first version uses the `@Route` annotation to define the endpoint `/template` with the name "template". The method name `indexAction()` is used but any name will work. This first version uses the service container (`$this->container`) to get an instance of the TemplateRepository, and the router params (`$request->route->params`) to get the route `{id}` value.

The file should be saved as `{projectDir}/src/Controller/Template/IndexController.php`.

```php
<?php
namespace Controller\Template;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use Repository\TemplatesRepository;

/**
 * Controller class
 */
class TemplateController extends Controller
{
    /**
     * @Route("/template/{id}", name="template")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            $this->throwUnauthorized();
        }

        $id         = $request->route->params->get('id');
        $repository = $this->container->get(TemplatesRepository::class);
        if (!$repository->hasAccess($user['usr_id'], $id)) {
            $this->throwUnauthorized();
        }

        $template = $repository->findByID($id);
        if (!$template) {
            $this->throwNotFound();
        }

        return $this->render('template/index.html.twig', [
            'template' => $template
        ]);
    }
}
```

#### Refactoring
Automatic injection passes request values into the action method. Values like the ID of the user, the request params like `{id}` and others. This refactor injects the user id (`$uid`), request param `{id}` (`$id`), and the `TemplateRepository` (`$templateRepository`) automatically into the action method. The code also uses the `@IsGranted()` annotation to control access to the controller to authenticated visitors.

```php
<?php
namespace Controller\Template;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\Response;
use Exception;
use Repository\TemplatesRepository;

/**
 * @IsGranted({"USER"})
 */
class TemplateController extends Controller
{
    /**
     * @Route("/template/{id}", name="template")
     *
     * @param int                 $id         Injected value of the {id} route param
     * @param int                 $uid        Injected ID of the user making the request
     * @param TemplatesRepository $repository Injected to query the database
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(
        int $id,
        int $uid,
        TemplatesRepository $repository
    )
    {
        if (!$repository->hasAccess($uid, $id)) {
            $this->throwUnauthorized();
        }
        $template = $repository->findByID($id);
        if (!$template) {
            $this->throwNotFound();
        }

        return $this->render('template/index.html.twig', [
            'template' => $template
        ]);
    }
}
```

#### Further Refactoring
The final refactor uses the `@IsGranted({"template"})` to guard access to the requested template. The annotation fetches the template from the database with the id that matches the request param `{id}` (configurable) and throws a `UnauthorizedException` when the authenticated user does not have access to the template.

The controller uses the `@InjectTemplate()` to automatically inject the template (`array $template`) into the action method by fetching the template from the database with the id that matches the request param `{id}` (configurable) and throws a `NotFoundException` when the template does not exist. The annotation does not check if the user has access to the template.

```php
<?php
namespace Controller\Template;

use BlocksEdit\Http\Annotations\InjectTemplate;
use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\Response;
use Exception;

/**
 * @IsGranted({"USER"})
 */
class TemplateController extends Controller
{
    /**
     * @IsGranted({"template"})
     * @Route("/template/{id}", name="template")
     * @InjectTemplate()
     *
     * @param array $template Injected by the @InjectTemplate() annotation
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(array $template)
    {
        return $this->render('template/index.html.twig', [
            'template' => $template
        ]);
    }
}
```

#### Automatic Injection
Automatic injected parameters can be in any order. The `ControllerInvoker` class examines at the names of the method parameters as well as the types. The following values are injected by examining the parameter names (the type hints don't matter).

* `int $uid`         - The authenticated user's ID
* `int $oid`         - The ID of the organization being requested
* `array $user`      - The authenticated user's full details
* `array $org`       - The requested organization full details
* `Request $request` - The request object

Route parameters defined in the `@Route` annotation are also injected. For example `@Route("/template/{id}/display/{size}", name="template_display")` will inject the values `int $id` and `string $size`.

Any other parameters found on the controller method will be injected by the service container. In the next example the service container will be called to inject the values `TemplatesRepository $repository` and `Mailer $mailer`.

```php
use BlocksEdit\Email\SmtpMailer;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\Annotations\IsGranted;
use Repository\TemplatesRepository;

/**
 * @IsGranted({"USER"})
 */
class TemplateController extends AbstractController
{
    /**
     * @Route("/template/{id}", name="template")
     *
     * @param int                 $id
     * @param int                 $uid
     * @param TemplatesRepository $repository
     * @param SmtpMailer              $mailer
     */
    public function indexAction(
        int $id,
        int $uid,
        TemplatesRepository $repository,
        SmtpMailer $mailer
    )
    {
        // ...
    }
}
```

### Repositories
@todo

### Entities
@todo

### Views
@todo

### Middleware
@todo

### Commands
@todo

### CSS
The framework uses a patchwork of CSS created by different developers. There's are no CSS frameworks like Bootstrap and Foundation, but utility classes have been borrowed from Bootstrap. The following is a list of those classes and the links to documentation on getbootstrap.com.

* [Display](https://getbootstrap.com/docs/4.0/utilities/display/)
* [Margin & Padding](https://getbootstrap.com/docs/4.0/utilities/spacing/)
* [Flex](https://getbootstrap.com/docs/4.0/utilities/flex/)
* [Text](https://getbootstrap.com/docs/4.0/utilities/text/)
* [Sizing](https://getbootstrap.com/docs/4.0/utilities/sizing/)
* [Colors](https://getbootstrap.com/docs/4.0/utilities/colors/)
* [Visibility](https://getbootstrap.com/docs/4.0/utilities/visibility/)
* [Positions](https://getbootstrap.com/docs/4.0/utilities/position/)
* [Float](https://getbootstrap.com/docs/4.0/utilities/float/)
* [Borders](https://getbootstrap.com/docs/4.0/utilities/borders/)
