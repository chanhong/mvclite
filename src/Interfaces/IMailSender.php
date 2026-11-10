<?php
namespace MvcLite\Interfaces;

interface IMailSender
{
    public function send(string $to, string $subject, string $message, string $from): void;
}
