<?php

namespace TickTackk\DeveloperTools\Seed;

use XF\Mvc\Entity\Entity;

/**
 * Class User
 *
 * @package TickTackk\DeveloperTools\Seed
 */
class User extends AbstractSeed
{
    protected $limit = 100000;

    /**
     * @param array|null $errors
     *
     * @return array|bool|Entity
     */
    protected function seedInternal(array &$errors = null)
    {
        $faker = $this->faker();

        /** @var \XF\Service\User\Registration $registrationService */
        $registrationService = $this->service('XF:User\Registration');
        $registrationService->setMapped([
            'username' => $faker->userName,
            'email' => $faker->email,
            'timezone' => $faker->timezone,
            'location' => $faker->boolean ? $faker->city : ''
        ]);
        $registrationService->setPassword($faker->password, '', false);
        $registrationService->setReceiveAdminEmail($faker->boolean);

        $dob = explode('-', $faker->dateTimeThisCentury->format('d-m-Y'));
        $registrationService->setDob($dob[0], $dob[1], $dob[2]);
        $registrationService->skipEmailConfirmation(true);

        if ($registrationService->validate($errors))
        {
            return $registrationService->save();
        }

        return false;
    }
}