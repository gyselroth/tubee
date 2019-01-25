# Jobs

Jobs are required to create a synchronization deployment. Unlinke [Processes](resources/processes.md) they are a persistent resource and work 
as a template for processes. Each job will trigger processes. 
A process defines when, how often and what endpoints get synchronized. Jobs trigger processes which are managed by the tubee task scheduler 
which is based on [\TaskScheduler`](https://github.com/gyselroth/mongodb-php-task-scheduler).
Jobs define what endpoints trigger parallel processes and in what order, they also define intervaly, retry levels and timeouts.

## Create job

```yaml
name: hourly
kind: Job
namespace: kzu
data:
  notification:
    enabled: false
    receiver: []
  collections:
  - ["accounts", "courses", "groups"]
  endpoints: 
  - mssql-import
  - mssql-relations
  - ["ldap-export", "balloon-export"]
  simulate: false
  log_level: debug
  ignore: true
  options:
    at: 0
    interval: 3600
    retry: 0
    retry_interval: 0
    timeout: 0
```

```sh
tubectl create -f spec.yaml
```

Check the just created resource:

```sh
tubectl get jobs hourly -n playground -o yaml
```

## Parallelism

It is important to understand how jobs trigger processes and how they work in parallel using the maximum amount of resources (Nodes and cpu cores).

### Synchron processes

To create a simple process order whereas endpoint named `a` should be processed first and as soon as it finishes it will trigger a second process for endpoint `b`, 
both for the collection named `accounts`.

```yaml
data:
  collections: 
  - accounts
  endpoints:
  - a
  - b
```

This job configuration will trigger a total of three processes:

1. The main process
2. The sync process for the endpoint a
3. The sync process for the endpoint b


>**Note** The main process is always finished as soon as all child processes were executed.

### Parallel processes

To create parallel processes, one may specify a list of endpoints and/or collections:

```yaml
data:
  collections: 
  - ['accounts', 'groups']
  endpoints:
  - a
  - b
```

This will trigger a total of 5 processes:

1. The main process
2. One process for accounts.a and one for groups.a at the same time 
3. One process for accounts.b and one for groups.b

## Simulation
A job can be entirely simulated by specify `data.simulate` to `true`. The default is `false`.
While simulation is enabled, everything gets executed as usual but actions only get simulated. There will be no changes, neither on tubee nor on any endpoints.

## Logging
By default jobs get executed within a log level `error`. This log level may be changed to one of:

* emergency
* critical
* error
* warning
* notice
* info
* debug

Be very careful with low levels like `debug`. Low log levels have a massive impact on the performance and should only be used during initial testing
and conifguration.

## Continue on error

Normally a process terminates as soon as it encounters an exception. By setting `data.ignore` to `true` the processor will ignore such errors and continues with 
the next object. The default is `false` but it is usually safe and a good idea to set it to `true`.

## Notification

A job might trigger mail notification as soon as it has been executed. Notification is disabled by default
but may be enabled by setting `data.notification.enabled` to `true`.
A notification may be sent to multiple receiver but at least one needs to be specified:

```
data:
  notification: 
    enabled: true
    receiver:
    - admin@example.org
```

## Job timing
By default a jobs triggers only once and never again. Usually this not what is wanted.
One may specify an interval time to let a job retrigger. It is also possible to set a specific time
when the job should trigger the first time.

This setup will lead to an immediate trigger as soon as the job gets created and will retrigger every hour.
```
data:
  options:
    at: 0
    interval 3600
```

The option `data.options.at` is by default `0` which means immedieately but it may be changed to a unix timestamp.
It will trigger a process at the time given.

## Retry & Errors

If a job fails (or one of its processes), it may trigger a retry process. By default this mechanism is disabled but might be enabled 
by specifying a retry number `data.options.retry`. `2` would mean the process should get triggered up to two times if it fails.
If a retry gets configured, it is best practics to define an interval, otherwise the time slot between failures might be too low that any issue 
was resolved in the meantime.

This example will trigger up to two times with an interval of 30min (three inclunding the first try):
```yaml
data:
  options:
    retry: 2
    retry_interval: 1800
```

If `data.options.ignore` is `true` there are still some circumstandes whereas a process might fail, for example if an endpoint can not get initialized due network
errors.

## Timeouts

It is possible to configure a timeout `data.options.timeout` which is by default `0` (No timeout). Be careful with timeouts as they leave endpoints
in incomplete conditions. 
