<?php
namespace MvcLite\Interfaces;

interface IPasswordRehasher
{
    public function rehashLegacyPassword(string $username, string $password, string $database): void;
}
