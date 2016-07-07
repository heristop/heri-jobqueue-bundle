# JobQueueBundle

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a6f86442-5e9c-4adf-bb23-d734c637b8cd/mini.png)](https://insight.sensiolabs.com/projects/a6f86442-5e9c-4adf-bb23-d734c637b8cd) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/heristop/HeriJobQueueBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/heristop/HeriJobQueueBundle/?branch=master) [![Build Status](https://travis-ci.org/heristop/HeriJobQueueBundle.svg)](https://travis-ci.org/heristop/HeriJobQueueBundle)

This bundle provides the use of `Zend Queue` from Zend Framework. It allows your Symfony 2/3 application to schedule multiple console commands as server-side jobs.

See the [Programmer's Reference Guide](http://framework.zend.com/manual/1.9/en/zend.queue.html) for more information.

Features:

 - Manage multiple queues
 - Schedule your Symfony commands
 - Monitor messages and log exceptions
 - Retry logic
 - Prioritizing jobs
 - RabbitMQ or database support
 - Easy-to-use :)

## Installation

Require `heristop/jobqueue-bundle` to your `composer.json` file:

```js
{
    "require": {
        "heristop/jobqueue-bundle": "dev-master"
    }
}
```

Load the bundle in AppKernel:

```php
    $bundles[] = new Heri\Bundle\JobQueueBundle\HeriJobQueueBundle();
```

If you use the Doctrine adapter, update your database:

```sh
    app/console doctrine:schema:update --force
```

If you use the Amqp adapter, you may configure the connection in this way:

```yaml
    # app/config/config.yml
    heri_job_queue:
        amqp_connection:
            host: localhost
            port: 5672
            user: guest
            password: guest
```

## Usage

First, define a message which contains the Symfony command to call. For instance, we choose to add the clear command in a queue named "queue1":

```php
    $queue = $this->get('jobqueue');
    $queue->attach('queue1');
    
    $queue->push([
        'command' => 'cache:clear'
    ]);
```

You can also call commands with arguments:

``` php
    $queue->push([
        'command'   => 'demo:great',
        'argument'  => [
            'name'   => 'Alexandre',
            '--yell' => true
        ]
    ]);
```

Then, add the queue to listen in the configuration:

```yaml
    # app/config/config.yml
    heri_job_queue:
        enabled:            true
        max_messages:       1
        # set a process_timeout (in seconds) if you want your commands to be killed
        # when their execution time is too long (default: null)
        process_timeout:    60
        queues:             [ queue1 ]
```

Note: The queue is automatically created, but you can also use the command-line interface in this way:

```sh
    app/console jobqueue:create queue1
```

## Listener Commands

### Run the Listener

To run new messages pushed into the queue, execute this command: 

```sh
    app/console jobqueue:listen
```

### Specify a specific Queue

You may specify which queue connection the listener should utilize (skipping configuration):

```sh
    app/console jobqueue:listen queue1
```

### Specify the Sleep Duration

You may also specify the number of seconds to wait before polling for new jobs:

```sh
    app/console jobqueue:listen --sleep=5
```

### Process the first Job on the Queue

To process only the first job on the queue, you may use the `jobqueue:work` command:

```sh
    app/console jobqueue:work
```

### Show Jobs

To see the pending jobs, run the command below:

```sh
    app/console jobqueue:show [queue-name]
```

## Failed Jobs

If a job failed, the exception is logged in the database, and the command is call again after the setted timeout (default 90 seconds):

![ScreenShot](https://raw.github.com/heristop/HeriJobQueueBundle/master/Resources/doc/console.png)

To delete all of your failed jobs, you may use the `jobqueue:flush` command:

```sh
    app/console jobqueue:flush [queue-name]
```

## Jobs Priority

Jobs are executed in the order in which they are scheduled (assuming they are in the same queue). You may also prioritize a call:

```php
    $queue
        ->highPriority()
        ->push([
            'command' => 'cache:clear',
        ]);
```

## Jobs Monitoring

If you use the Doctrine Adapter, you may use Sonata Admin to monitor your jobs:

```yaml
    #
    # more information can be found here http://sonata-project.org/bundles/admin
    #
    sonata_admin:
        # ...
        dashboard:
            # ...
            groups:
                # ...
                System:
                    label:           System
                    icon:            '<i class="fa fa-wrench"></i>'
                    items:
                        - admin.queue
```

![ScreenShot](https://raw.github.com/heristop/HeriJobQueueBundle/master/Resources/doc/sonataadmin.png)

## Retry strategy

By default, number of excecution of failed messages is endless. If you use the Doctrine Adapter you may edit the max number of retries on queue table.

To retry all of your failed jobs, you may use this command:

```sh
    app/console jobqueue:retry [queue-name]
```

If you would like to delete a failed job, you may use this command:

```sh
    app/console jobqueue:forget [id]
```

## Configure a daemon

The `jobqueue:listen` command should be runned with the prod environnement and the quiet option to hide output messages:

```sh
    app/console jobqueue:listen --env=prod --quiet
```

To avoid a memory leak caused by the monolog fingers crossed handler, you may configure the limit buffer size on `config_prod.yml`:

```yaml
    # app/config/config_prod.yml
    monolog:
        handlers:
            main:
                type:         fingers_crossed
                action_level: error
                handler:      nested
                buffer_size:  50
```

Linux ProTip:

To run the command as a service, edit `jobqueue-service` shell in `Resources/bin`.
Set the correct `PROJECT_ROOT_DIR` value, and copy this file to `/etc/init.d`.

Then use update-rc.d:

```sh
    cp jobqueue-service /etc/init.d/jobqueue-service
    cd /etc/init.d && chmod 0755 jobqueue-service
    update-rc.d jobqueue-service defaults
```

To remove the service, use this command:

```sh
    update-rc.d -f jobqueue-service remove
```

If the service stopped suddenly, you may use `supervisord` to restart it automatically.

A sample config might look like this:

```sh
  [program:jobqueue-service]
  command=/usr/bin/php %kernel.root_dir%/console jobqueue:listen --env=prod
  directory=/tmp
  autostart=true
  autorestart=true
  startretries=3
  stderr_logfile=/var/log/jobqueue-service/jobqueue.err.log
  stdout_logfile=/var/log/jobqueue-service/jobqueue.out.log
  user=www-data
~
```
