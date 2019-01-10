<?php

namespace TickTackk\DeveloperTools\Seed;

/**
 * Class Post
 *
 * @package TickTackk\DeveloperTools\Seed
 */
class Post extends AbstractSeed
{
    /**
     * @var int
     */
    protected $limit = 500;

    /**
     * @param array|null $errors
     *
     * @return array|bool|\XF\Mvc\Entity\Entity
     */
    protected function seedInternal(array &$errors = null)
    {
        $faker = $this->faker();
        if ($randomThread = $this->randomEntity('XF:Thread'))
        {
            /** @var \XF\Service\Thread\Replier $threadReplier */
            $threadReplier = $this->service('XF:Thread\Replier', $randomThread);
            $threadReplier->setIsAutomated();
            $ipAddress = $faker->ipv4;
            if (!$faker->boolean)
            {
                $ipAddress = $faker->ipv6;
            }
            $threadReplier->logIp($ipAddress);
            $threadReplier->setMessage(implode("\n", $faker->paragraphs($faker->numberBetween(1, 6))));
            if ($threadReplier->validate($errors))
            {
                return $threadReplier->save();
            }
        }

        return false;
    }
}