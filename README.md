# JobQueueBundle

This bundle provides the use of Zend_Queue from Zend Framework. It allows your application to manage multiple jobs from a variety of sources.

See the [Programmer's Reference Guide](http://framework.zend.com/manual/fr/zend.queue.html) for more information.

## Installation

Download source from github:

```ini
    [HeriJobQueueBundle]
    git=https://github.com/heristop/HeriJobQueueBundle.git
```

Load in AppKernel: 

```php
    $bundles[] = new Heri\JobQueueBundle\HeriJobQueueBundle();
```   

## ZF installation

Use this unofficial github mirror:

```ini
    [ZendFrameworkLibrary]
    git=https://github.com/tjohns/zf.git
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

## Fixtures

Create a queue. The queue below is named 'erp:front' for example:

```php
    namespace Heri\JobQueueBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\FixtureInterface;
    use Heri\JobQueueBundle\Entity\Queue;

    class Fixtures implements FixtureInterface
    {
        public function load($manager)
        {
            $queue = new Queue();
            $queue->setQueueName('erp:front');
            $queue->setTimeout(90);
            $manager->persist($queue);
            $manager->flush();
        }
    }
```

Create a message. For instance:

```php
    $queue = $this->get('jobqueue');
    $em = $this->get('doctrine.orm.entity_manager');

    $queue->configure('erp:front'), $em);
    
    $config = array(
        'command'   => 'webservice:load',
        'arguments' => array(
            '--record' => 1,              // option
            'model'    => 'Notification'  // argument
        ),
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
Set the correct JOBQUEUE_BUNDLE_PATH value, and copy this file to /etc/init.d.

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