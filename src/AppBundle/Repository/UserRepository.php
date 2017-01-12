<?php

namespace AppBundle\Repository;
use AppBundle\Entity\User;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends \Doctrine\ORM\EntityRepository
{
    public function getUser($data)
    {
        $user = null;
        if(isset($data->id)) {
            $user = $this->findOneByUserId($data->id);
        };
        if(!$user && isset($data->username)){
            $user = $this->findOneByUsername($data->username);
        }

        if(!$user){
            $user = new User();
        };
        $name = '';
        if(isset($data->first_name)){
            $name .= $data->first_name;
        }
        if(isset($data->last_name)){
            $name .= ' '.$data->last_name;
        };
        $name = trim($name);
        if($name) {
            $user->setName(trim($name));
        };
        $user->setUsername($data->username);
        if(isset($data->id)) {
            $user->setUserId($data->id);
        };

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function findByRandom($chatId)
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata('AppBundle\Entity\User', 'u');

        $sql = 'SELECT u.* FROM user u '
                .'INNER JOIN chat_players cp ON (cp.user_id = u.id) '
                .'INNER JOIN chat c ON (cp.chat_id = c.id) '
                .'WHERE c.chat_id = :chat_id '
                .'ORDER BY RAND() '
                .'LIMIT 1 '
        ;

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('chat_id', $chatId);

        return $query->getResult();
    }

}
