<?php

namespace App\Tests\Entity;

use App\Entity\Result;
use App\Entity\User;
use DateTime;
use Faker\Factory as FakerFactoryAlias;
use Faker\Generator as FakerGeneratorAlias;
use PHPUnit\Framework\TestCase;

/**
 * Class ResultTest
 *
 * @package App\Tests\Entity
 *
 * @group   entities
 * @coversDefaultClass \App\Entity\Result
 */
class ResultTest extends TestCase
{
  protected static Result $result;
  protected static User $user;
  protected static DateTime $date;
  private static FakerGeneratorAlias $faker;

  public static function setUpBeforeClass(): void
  {
    self::$date = new DateTime('2023-12-22');
    self::$user = new User();
    self::$result = new Result(10, self::$user, self::$date);
    self::$faker = FakerFactoryAlias::create('es_ES');
  }

  public function test__construct(): void
  {
    $user = new User('user@user.com');
    $date = new \DateTime('2023-12-22');
    $result = new Result(10, $user, $date);

    self::assertSame(0, $result->getId());
    self::assertSame(10, $result->getResult());
    self::assertSame($user, $result->getUser());
    self::assertSame($date, $result->getTime());
  }

  public function testGetId()
  {
    self::assertEquals(0, self::$result->getId());
  }

  public function testGetSetResult()
  {
    $result = self::$faker->randomDigitNotNull();
    self::$result->setResult($result);
    static::assertSame($result, self::$result->getResult());
  }

  public function testGetSetUser()
  {
    $userEmail = self::$faker->email();
    $password = self::$faker->password();
    $role = self::$faker->slug();

    self::$user->setEmail($userEmail);
    self::$user->setPassword($password);
    self::$user->setRoles([$role]);

    $user = new User($userEmail, $password, [$role]);

    self::$result->setUser($user);
    static::assertSame($user, self::$result->getUser());
  }

  public function testGetSetTime()
  {
    $date = self::$faker->dateTime();
    self::$result->setTime($date);
    static::assertSame($date, self::$result->getTime());
  }
}