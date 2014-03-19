# JobQueueBundle

This bundle provides the use of `Zend Queue` from Zend Framework. It allows your Symfony application to schedule multiple Symfony console commands as server-side jobs.

See the [Programmer's Reference Guide](http://framework.zend.com/manual/1.9/en/zend.queue.html) for more information.

## Installation

Require `heristop/jobqueue-bundle` to your `composer.json` file:

```js
{
    "require": {
        "heristop/jobqueue-bundle": "*@dev"
    }
}
```

Load the bundle in AppKernel: 

```php
    $bundles[] = new Heri\Bundle\JobQueueBundle\HeriJobQueueBundle();
```

Finaly, update your database:

```shell
    app/console doctrine:schema:update --force
```

## Configuration

Create a queue. For example, the queue below is named `my:queue1`:

```php
    namespace Heri\Bundle\JobQueueBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\FixtureInterface;
    use Heri\Bundle\JobQueueBundle\Entity\Queue;

    class Fixtures implements FixtureInterface
    {
        public function load($manager)
        {
            $queue = new Queue();
            $queue->setName('my:queue1');
            $queue->setTimeout(90);
            $manager->persist($queue);
            $manager->flush();
        }
    }
```

Define the queue to listen in the configuration:

```yaml
    heri_job_queue:  
        enabled:       true
        max_messages:  1
        queues:        [ my:queue1 ]
```

Then, we create a message which contains a Symfony command to call. For instance, we choose to add the clear command in the queue: 

```php
    $queue = $this->get('jobqueue');
    $queue->configure('my:queue1');
    
    $queue->sync(array('command' => 'cache:clear'));
```

We can also call commands with arguments:

```php
    $queue = $this->get('jobqueue');
    $queue->configure('my:queue1');
    
    $queue->sync(array(
        'command'   => 'demo:great',
        'argument'  => array(
            'name'   => 'Alexandre',
            '--yell' => true,
        )
    );
```

## Command

To run the JobQueue execute this command:

```shell
    app/console jobqueue:load
```

If a message failed, the exception is logged in the table `message_log`, and the command is call again after the setted timeout:

![ScreenShot](https://raw.github.com/heristop/HeriJobQueueBundle/master/Resources/doc/console.png)

## Service

To run the command as a service, edit `jobqueue-service` shell.
Set the correct JOBQUEUE_BUNDLE_PATH value, and copy this file to `/etc/init.d`.

Then use update-rc.d:

```shell
    cp jobqueue-service /etc/init.d/jobqueue-service
    cd /etc/init.d && chmod 0755 jobqueue-service
    update-rc.d jobqueue-service defaults
```

To remove the service, use this command:

```shell
    update-rc.d -f jobqueue-service remove
```

## Note

This bundle can be used with [HeriWebServiceBundle](https://github.com/heristop/HeriWebServiceBundle/) to manage multiple webservice connections.
The Doctrine Adapter is inspired by [SoliantDoctrineQueue] (https://github.com/TomHAnderson/SoliantDoctrineQueue).