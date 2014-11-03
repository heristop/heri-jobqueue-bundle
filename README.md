# JobQueueBundle

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a6f86442-5e9c-4adf-bb23-d734c637b8cd/mini.png)](https://insight.sensiolabs.com/projects/a6f86442-5e9c-4adf-bb23-d734c637b8cd)

This bundle provides the use of `Zend Queue` from Zend Framework. It allows your Symfony application to schedule multiple console commands as server-side jobs.

See the [Programmer's Reference Guide](http://framework.zend.com/manual/1.9/en/zend.queue.html) for more information.

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

Finaly, update your database:

```sh
    app/console doctrine:schema:update --force
```

## Configuration

To create a queue, you can use the command-line interface in this way:

```sh
    app/console jobqueue:create queue1
```

Add the created queue to listen in the configuration:

```yaml
    heri_job_queue:
        enabled:       true
        max_messages:  1
        queues:        [ queue1 ]
```

Then, define a message which contains a Symfony command to call. For instance, we choose to add the clear command in the queue:

```php
    $queue = $this->get('jobqueue');
    $queue->attach('queue1');
    
    $queue->push(array(
        'command' => 'cache:clear'
    ));
```

You can also call commands with arguments:

``` php
    $queue->push(array(
        'command'   => 'demo:great',
        'argument'  => array(
            'name'   => 'Alexandre',
            '--yell' => true
        )
    ));
```

## Listener Commands

### Run the Listener

To run new jobs pushed into the queue, execute this command: 

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
    app/console jobqueue:show queue1
```

## Failed Jobs

If a job failed, the exception is logged in the database, and the command is call again after the setted timeout (default 90 seconds):

![ScreenShot](https://raw.github.com/heristop/HeriJobQueueBundle/master/Resources/doc/console.png)

To delete all of your failed jobs, you may use the `jobqueue:flush` command:

```sh
    app/console jobqueue:flush
```

## Service

To run the command as a service, edit `jobqueue-service` shell in `Resources/bin`.
Set the correct JOBQUEUE_BUNDLE_PATH value, and copy this file to `/etc/init.d`.

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

## Note

This bundle can be used with [HeriWebServiceBundle](https://github.com/heristop/HeriWebServiceBundle/) to manage multiple webservice connections.
The Doctrine Adapter is inspired by [SoliantDoctrineQueue] (https://github.com/TomHAnderson/SoliantDoctrineQueue).
