# JobQueueBundle

This bundle provides the use of Zend_Queue from Zend Framework. It allows your application to manage multiple commands from a variety of sources.

See the [Programmer's Reference Guide](http://framework.zend.com/manual/fr/zend.queue.html) for more information.

## Installation

Download sources from github:

```ini
    [HeriJobQueueBundle]
        git=https://github.com/heristop/HeriJobQueueBundle.git
        target=/bundles/Heri/JobQueueBundle/
```

Register namespace in autoload:

```php
    $loader->registerNamespaces(array(
        ...
        'Heri' => __DIR__.'/../vendor/bundles',
    ));
```

Load the bundle in AppKernel: 

```php
    $bundles[] = new Heri\JobQueueBundle\HeriJobQueueBundle();
```

Update your database:

```shell
    app/console doctrine:schema:update --force
```

## ZF Installation

Use this unofficial github mirror:

```ini
    [ZendFrameworkLibrary]
        git=https://github.com/tjohns/zf.git
        target=/zf
```

Register a prefix in AppKernel:

```php
    $loader->registerPrefixes(array(
        ...
        'Zend_' => __DIR__.'/../vendor/zf/library',
    ));
```

Following the [official ZF documentation](http://framework.zend.com/manual/en/performance.classloading.html#performance.classloading.striprequires.sed), remove all _require_once()_:

```shell
    $ cd vendor/zf/library
    $ find . -name '*.php' -not -wholename '*/Loader/Autoloader.php' \
    -not -wholename '*/Application.php' -print0 | \
    xargs -0 sed --regexp-extended --in-place 's/(require_once)/\/\/ \1/g'
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
            $queue->setQueueName('my:queue');
            $queue->setTimeout(90);
            $manager->persist($queue);
            $manager->flush();
        }
    }
```

Messages related to this queue will be called every 90 seconds.

How to create a message?

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

At the end, define the queue(s) to listen:

```yaml
    heri_job_queue:  
        enabled:  true
        queues:   [ my:queue ]
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
    cd /etc/init.d && chmod 755 jobqueue-service
    update-rc.d jobqueue-service defaults
```

To remove the service, use this command:

```shell
    update-rc.d -f jobqueue-service remove
```

## Note

This bundle can be used with [HeriWebServiceBundle](https://github.com/heristop/HeriWebServiceBundle/) to manage multiple webservice connections.