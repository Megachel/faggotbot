<?php

namespace AppBundle\Controller;

use AppBundle\Service\MessageQueryService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
    }

    /**
     * @Route("/webHook", name="webhook")
     */
    public function webhookAction(Request $request)
    {
        file_put_contents('webhook.log', date('Y-m-d H:m:i') .' REQUEST: '. $request->getContent() . PHP_EOL, FILE_APPEND);
        $result = null;
        try{
//            file_put_contents('request.json', $request->getContent());
            $requestData = json_decode($request->getContent());

            if(isset($requestData->message)){
                $this->proceedMessage($requestData->message);
            };
        }catch(\Exception $e){
            die($e->getMessage());
        };
        return new JsonResponse(['status' => true]);
    }

    public function proceedMessage($message)
    {
        /** @var MessageQueryService $messageService */
        $messageService = $this->get('message_query_service');
        $messageService->proceed($message);
    }
}
