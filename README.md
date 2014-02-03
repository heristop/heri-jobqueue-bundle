# JobQueueBundle

This bundle provides the use of Zend Queue from Zend Framework. It allows your application to manage multiple commands from a variety of sources.

See the [Programmer's Reference Guide](http://framework.zend.com/manual/fr/zend.queue.html) for more information.

## Installation

Download sources from github:

```ini
    [HeriJobQueueBundle]
        git=https://github.com/heristop/HeriJobQueueBundle.git
        target=/bundles/Heri/JobQueueBundle/
```

Or use composer adding the requirement below:

``` js
{
    "require": {
        "heristop/jobqueue-bundle": "*"
    }
}
```


Load the bundle in AppKernel: 

```php
    $bundles[] = new Heri\JobQueueBundle\HeriJobQueueBundle();
```

Update your database:

```shell
    app/console doctrine:schema:update --force
```

## Configuration

Create a queue. For example, the queue below is named _my:queue_:

```php
    namespace Heri\JobQueueBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\FixtureInterface;
    use Heri\JobQueueBundle\Entity\Queue;

    class Fixtures implements FixtureInterface
    {
        public function load($manager)
        {
            $queue = new Queue();
            $queue->setName('my:queue');
            $queue->setTimeout(90);
            $manager->persist($queue);
            $manager->flush();
        }
    }
```

Define the queue to listen in the configuration:

```yaml
    heri_job_queue:  
        enabled:  true
        queues:   [ my:queue ]
```

Then we will create a message, for the example, which contains a Symfony command to call:

```php
    $queue = $this->get('jobqueue');
    $queue->configure('my:queue');
    
    $config = array(
        'command'   => 'demo:great',
        'argument'  => array(
            'name'   => 'Alexandre',
            '--yell' => true,
        )
    );
    
    $queue->sync($config);
```

## Command

Move in _Heri/JobQueueBundle/Command_ directory and launch this command:

```shell
    sh start-queue-manager &
```

## Service

To run the command as a service, edit _jobqueue-service_ shell.
Set the correct JOBQUEUE_BUNDLE_PATH value, and copy this file to _/etc/init.d_.

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
