<?php

require('../vendor/autoload.php');
use Silex\Provider\FormServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Validator\Constraints as Assert;


$app = new Silex\Application();
$app['debug'] = true;
$app['emailFrom'] = 'demomail@gmail.com';
$app['emailPass'] = 'demopassword';
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

$app->get('/', function() use($app) {
  $app['monolog']->addDebug('logging home page.');
  return $app['twig']->render('pages/index.twig', array(
    //'error' => $app['security.last_error']($request),
      'error' => 'Contact us using the form below and we\'ll get back in touch with you',
  ));
})->bind('home');

$app->match('/contact', function(Request $request) use ($app) {
    $sent = false;
    $default = array(
        'name' => '',
        'email' => '',
        'message' => '',
        'verify' => '',
    );
    $form = $app['form.factory']->createBuilder('form',$default)
        ->add('name', 'text', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 3))),
			'attr' => array('class' => 'form-control', 'placeholder' => 'Your Name', 'error' => 'Name should be greater than 3 characters')
		))
		->add('email', 'email', array(
			'constraints' => new Assert\Email(),
			'attr' => array('class' => 'form-control', 'placeholder' => 'Your@email.com', 'error' => 'Please verify your email. Eg.Your@email.com ')
		))
		->add('message', 'textarea', array(
			'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 20))),
			'attr' => array('class' => 'form-control', 'placeholder' => 'Enter Your Message', 'error' => 'Please enter your query here.')
		))
        ->add('verify', 'text', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 1, 'max' => 1)), new Assert\EqualTo(array('value' => 9)) ),            
			'attr' => array('class' => 'form-control', 'placeholder' => '2 + 7 = ?')            
		))
		->add('Enquire Now', 'submit', array(
			'attr' => array('class' => 'btn btn-default btn-primary wow animated swing')
		))
		->getForm();
 
	   $form->handleRequest($request);

       
            if($form->isValid()) {
                $data = $form->getData();    
                $exit = false;

                $name = strip_tags($data['name']);
                $subject = $app['mailSubject'].$name;
                $mailfrom =  strip_tags($data['email']);
                $emailTo = array($app['emailFrom']);
                $messagebody = strip_tags($data['message']);
                $verifyKey = strip_tags($data["verify"]);
    
                if(!$exit){
                    if($verifyKey == 9){

                            $message = \Swift_Message::newInstance()
                            ->setSubject($subject)
                            ->setFrom($mailfrom)
                            ->setTo($emailTo)
                            ->setBody($app['twig']->render('email.twig',   // email template
                                array('name'      => $name,
                                      'mailfrom'  => $mailfrom,
                                      'message'   => $messagebody,
                                )),'text/html');
                            //->setBody($messagebody);
                        
                            $app['mailer']->send($message);                
                            $sent = true;  
                    }
                    else{
                        //do something
                        $exit = true;
                        $sent = false;
                    }
                }
            }

            return $app['twig']->render('pages/contact.twig', array('form' => $form->createView(), 'sent' => $sent));
        
    })->bind('contact');

    $app->error(function (\Exception $e, $code) use ($app) {
        if ($app['debug']) {
            return;
        }
        switch ($code) {
            case 404:
                $message = 'The requested page could not be found.';
                break;
            default:
                $message = 'We are sorry, but something went terribly wrong.';
        }
        return new Response($message, $code);
    });

$app->run();

?>