<?php

namespace App\Services;

use App\Models\User;
use App\Models\PersonalAccessToken;

class AuthenticationService
{
  public static function verifyTokenValidity($token)
  {
    if ($token != null) {
      $personnalAccessToken = PersonalAccessToken::findToken($token);
      if ($personnalAccessToken != null) {
        return ['personnalAccessToken' => $personnalAccessToken, 'currentUser' => self::getCurrentUser($personnalAccessToken)];
      }
      return ['personnalAccessToken' => $personnalAccessToken];
    }
  }

  public static function getCurrentUser($personnalAccessToken)
  {
    return User::find($personnalAccessToken['tokenable_id']);
  }
}
