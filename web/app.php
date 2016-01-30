<?php 
use Silex\Provider\FormServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Validator\Constraints as Assert;


$app['debug'] = false;
$app['baseUrl'] = 'http://localhost/sainivaraslim/web';
$app['emailFrom'] = 'nitesh.patare27@gmail.com';
$app['emailPass'] = 'premiumgold74g';
$app['mailSubject'] = 'Sai Prasad Nivara Enquiry By:- ';

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));
$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addFunction(new \Twig_SimpleFunction('asset', function ($asset) use ($app) {
        return sprintf('%s/%s', trim($app['request']->getBasePath()), ltrim($asset, '/'));
    }));
    return $twig;
}));

$app->register(new Silex\Provider\SecurityServiceProvider());


$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());

$app->register(new Silex\Provider\SwiftmailerServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.domains' => array(),
));
$app->register(new FormServiceProvider());
$app->register(new Silex\Provider\SwiftmailerServiceProvider());

$app['swiftmailer.options'] = array(
	'host' => 'smtp.gmail.com',
	'port' => 465,
	'username' => $app['emailFrom'],
	'password' => $app['emailPass'],
	'encryption' => 'ssl',
);

// Our web handlers
$app['security.firewalls'] = array(
    'site' => array(
        'anonymous' => true,
        'pattern' => '^.*$',
        'http' => true,
        'form' => array(
            'contact_path' => '/contact', 
        ),
        
    ),
);


$app['security.access_rules'] = array(
    array('^/', 'IS_AUTHENTICATED_ANONYMOUSLY')
);

$app->before(function(Request $request) use ($app){
    $app['twig']->addGlobal('active', $request->get("_route"));
});