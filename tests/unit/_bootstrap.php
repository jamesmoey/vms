<?php
// Here you can initialize variables that will for your tests
require_once __DIR__.'/../../app/bootstrap.php.cache';
require_once __DIR__.'/../../app/AppKernel.php';

$kernel = new AppKernel('test', true);
$kernel->boot();

$application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
$application->setAutoExit(false);

$option = [
    '--env'=>'test',
    '--quiet'=>true,
];

$application->run(new \Symfony\Component\Console\Input\ArrayInput(array_merge($option, [
    'command'=>'doctrine:schema:drop',
    '--force'=>true,
])));

$application->run(new \Symfony\Component\Console\Input\ArrayInput(array_merge($option, [
    'command'=>'doctrine:schema:create',
])));


$kernel->shutdown();