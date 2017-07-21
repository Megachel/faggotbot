<?php
namespace AppBundle\Service;

use AppBundle\Entity\Chat;
use AppBundle\Entity\Stat;
use AppBundle\Entity\User;
use AppBundle\Repository\StatRepository;
use AppBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManager;

class MessageQueryService
{
    private $em;
    private $telegramService;
    private $botId;
    private $botUsername;
    private $authorNowPlaying;

    public function __construct(EntityManager $em, TelegramService $telegramService, $botId, $botUsername)
    {
        $this->em = $em;
        $this->telegramService = $telegramService;
        $this->botId = $botId;
        $this->botUsername = $botUsername;
        $this->authorNowPlaying = false;
    }

    public function proceed(\stdClass $message)
    {
        if($message->chat->type === 'private') {
            $this->telegramService->sendMessage(
                $message->chat->id,
                'Сюда не пиши, в общий чат пиши.'
            );
            return true;
        };
        if(isset($message->new_chat_member)){
            // Бота добавили в чатик
            if($message->new_chat_member->id == $this->botId){
                if($this->addedToChat($message->chat)){
                    $this->telegramService->sendMessage(
                        $message->chat->id,
                        'Приветики! Напиши /pidoreg если чувствуешь себя неуверенно.'
                    );
                }
            }
        };
        if(isset($message->entities)) foreach($message->entities as $entity){
            if($entity->type === 'bot_command'){
                $this->proceedCommand($message, $entity->offset, $entity->length);
            }
        }

        return true;
    }

    private function proceedCommand($message, $offset = 0, $length = 0)
    {
        if($length){
            $command = substr($message->text, $offset, $length);
        }else{
            $command = $message->text;
        }
        $command = trim(trim($command), '/');
        $commandArr = explode('@', $command);
        if(isset($commandArr[1])
            && strtolower($commandArr[1]) != strtolower($this->botUsername)){
            return false;
        }

        $this->checkUser($message);
        switch($commandArr[0]){
            case 'pidor': $this->commandPidor($message); break;
            case 'pidoreg': $this->commandPidoreg($message); break;
            case 'stat': $this->commandStat($message); break;
            case 'dates': $this->commandDates($message); break;
        }

        if($this->authorNowPlaying){
            $this->telegramService->sendMessage(
                $message->chat->id,
                '@' . $message->from->username . ', привет, хорошенький!'
            );
        }
        exit;
    }

    private function checkUser($message)
    {
        /** @var Chat $chat */
        $chat = $this->em->getRepository('AppBundle:Chat')->findOneBy(['chatId' => $message->chat->id]);
        /** @var User $user */
        $user = $this->em->getRepository('AppBundle:User')->getUser($message->from);

        $messageAuthorPlaying = $chat->getPlayers()->contains($user);
        if(!$messageAuthorPlaying){
            $chat->addPlayer($user);
            $this->em->persist($chat);
            $this->em->flush();
            $this->authorNowPlaying = true;
        }
    }

    private function commandDates($message)
    {
        /** @var StatRepository $statRepository */
        $statRepository = $this->em->getRepository('AppBundle:Stat');
        $stat = $statRepository->getUserChatStat($message->from->id, $message->chat->id);
        if($stat) {
            $text = '@'.$message->from->username .", вот дни твоей славы:\n";
            foreach($stat as $item){
                $text .=  $item->format('d.m.Y') . PHP_EOL;
            }
        }else{
            $text = '@'. $message->from->username .' такой чести тебе не выпадало';
        };
        $this->telegramService->sendMessage(
            $message->chat->id,
            $text
        );
    }

    private function commandStat($message)
    {
        /** @var StatRepository $statRepository */
        $statRepository = $this->em->getRepository('AppBundle:Stat');
        $stat = $statRepository->getChatStat($message->chat->id);
        if($stat) {
            $text = "Рад объявить победителей:\n";
            foreach($stat as $item){
                if($item['cnt'] == 1) {
                    $text .= '@' . $item['username'] . " *" . $item['cnt'] . ' раз* не пидорас' . PHP_EOL;
                }else{
                    $text .= '@' . $item['username'] . " пидор *" . $item['cnt'] . ' '.
                        $this->wordForm($item['cnt'], ['раз', 'раза', 'раз']) .
                        '*' . PHP_EOL;
                }
            }
        }else{
            $text = "А победителей пока нет, начинай играть командой /pidor";
        };
        $this->telegramService->sendMessage(
            $message->chat->id,
            $text
        );
    }

    private function wordForm($number, $forms)
    {
        $cases = [2, 0, 1, 1, 1, 2];
        return $forms[ ($number % 100 > 4 && $number % 100 < 20) ? 2: $cases[min($number % 10, 5)] ];
    }

    private function commandPidor($message)
    {
        /** @var Chat $chat */
        $chat = $this->em->getRepository('AppBundle:Chat')->findOneBy(['chatId' => $message->chat->id]);

        $midnight = (new \DateTime())->setTime(0,0,0);
        if($chat->getLastPidorTime() > $midnight){
            $this->telegramService->sendMessage(
                $message->chat->id,
                'Рулетка "*счастье сфинктора*" на сегодня '.
                ($chat->getLastPidor()->getUsername() === 'querystring' ? 'Почетным ':'').
                'пидором дня объявила @'. $this->telegramService->usernameEncode($chat->getLastPidor()->getUsername())
            );
            return false;
        }
        /** @var UserRepository $userRepository */
        $userRepository = $this->em->getRepository('AppBundle:User');

        $user = $userRepository->findByRandom($message->chat->id);
        if(count($user)){
            $user = $user[0];
            $chat->setLastPidor($user);
            $chat->setLastPidorTime(new \DateTime());
            $this->em->persist($chat);
            $stat = new Stat();
            $stat->setChat($chat);
            $stat->setUser($user);
            $this->em->persist($stat);
            $this->em->flush();

            $this->telegramService->sendMessage(
                $message->chat->id,
                'Рулетка "*счастье сфинктора*" запущена!'
            );
            sleep(1);
            $this->telegramService->sendMessage(
                $message->chat->id,
                'Результат скоро появится'
            );
            sleep(1);
            $this->telegramService->sendMessage(
                $message->chat->id,
                'Я чувствую его головку...'
            );
            sleep(1);
            $this->telegramService->sendMessage(
                $message->chat->id,
                '_иииии_'
            );
            sleep(2);
            $this->telegramService->sendMessage(
                $message->chat->id,
                '*'. ($user->getUsername() === 'querystring' ? 'Почетным ':'')
                .'Пидором дня* объявляется @'.
                $this->telegramService->usernameEncode($user->getUsername())
            );
        }else{
            // yet no users in this chat registered
            $this->telegramService->sendMessage(
                $message->chat->id,
                'Покуда никто не регистрировался в Игре, то *пидор* - @'. $message->from->username
            );
        }
    }

    private function commandPidoreg($message)
    {
        /** @var Chat $chat */
        $chat = $this->em->getRepository('AppBundle:Chat')->findOneBy(['chatId' => $message->chat->id]);

        /** @var UserRepository $userRepository */
        $userRepository = $this->em->getRepository('AppBundle:User');

        $user = $userRepository->getUser($message->from);

        $mention = null;
        foreach($message->entities as $entity){
            if($entity->type == 'mention'){
                $mention = substr($message->text, $entity->offset+1, $entity->length);
            }
        };

        // Register a friend
        if($mention){
            $mentionedUser = $userRepository->getUser((object)['username' => $mention]);
            if($chat->getPlayers()->contains($mentionedUser)){
                $this->telegramService->sendMessage(
                    $message->chat->id,
                    '@' . $this->telegramService->usernameEncode($mentionedUser->getUsername()) . ' уже участвует в игре "Пидор дня"'
                );
            }else {
                $chat->addPlayer($mentionedUser);
                $this->em->persist($mentionedUser);
                $this->em->flush();

                $this->telegramService->sendMessage(
                    $message->chat->id,
                    '@' . $this->telegramService->usernameEncode($mentionedUser->getUsername()) . ', теперь ты участвуешь в игре "Пидор дня"'
                );
            };

            if($this->authorNowPlaying){
                $this->telegramService->sendMessage(
                    $message->chat->id,
                    '@' . $message->from->username . ', ты, кстати, тоже.'
                );
                $this->authorNowPlaying = false;
            }
        }else {
            // Self register
            if (!$this->authorNowPlaying) {
                $this->telegramService->sendMessage(
                    $message->chat->id,
                    '@' . $message->from->username . ', дважды пидором быть нельзя, как бы тебе этого не хотелось.'
                );
            } else {
                $this->authorNowPlaying = false;

                $this->telegramService->sendMessage(
                    $message->chat->id,
                    '@' . $message->from->username . ', теперь ты участвуешь в игре "Пидор дня"'
                );
            }
        };

    }

    private function addedToChat($chatData)
    {
        $isExist = $this->em->getRepository('AppBundle:Chat')->findByChatId($chatData->id);
        if(!$isExist){
            $chat = new Chat();
            $chat->setChatId($chatData->id);
            $chat->setName($chatData->title);
            $this->em->persist($chat);
            $this->em->flush();
            return true;
        }
        return false;
    }
}