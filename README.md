# JobQueueBundle

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
    $queue->configure('queue1');

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

## Command

To run the JobQueue execute this command:

```sh
    app/console jobqueue:load
```

If a message failed, the exception is logged in the table `message_log`, and the command is call again after the setted timeout (default 90 seconds):

![ScreenShot](https://raw.github.com/heristop/HeriJobQueueBundle/master/Resources/doc/console.png)

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
