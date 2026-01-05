<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Controller\Index;

use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\Response\Http as Response;
use Magento\Framework\App\Action\Context;
use MercadoPago\AdbPayment\Controller\Index\GenerateQrCode;
use PHPUnit\Framework\TestCase;

class GenerateQrCodeTest extends TestCase
{
    private $controller;
    private $requestMock;
    private $responseMock;
    private $contextMock;
    private $loggerMock;
    private $responseBody;

    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(Request::class);
        $this->responseMock = $this->createMock(Response::class);
        $this->contextMock = $this->createMock(Context::class);
        $this->loggerMock = $this->createMock(\Psr\Log\LoggerInterface::class);

        $this->contextMock->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->method('getResponse')->willReturn($this->responseMock);

        $this->responseMock->method('setHeader')->willReturnSelf();
        $this->responseMock->method('setHttpResponseCode')->willReturnSelf();
        $this->responseMock->method('setBody')->willReturnCallback(function ($body) {
            $this->responseBody = $body;
            return $this->responseMock;
        });

        $this->controller = new GenerateQrCode($this->contextMock, $this->loggerMock);
    }

    public function testExecuteWithoutDataParam()
    {
        $this->requestMock->method('getParam')->with('data')->willReturn(null);

        $this->loggerMock
            ->expects($this->once())
            ->method('debug')
            ->with(json_encode(['error' => "'data' parameter not provided."]));

        $this->controller->execute();
    }

    public function testExecuteWithInvalidBase64()
    {
        $this->requestMock->method('getParam')->with('data')->willReturn('invalid_base64');

        $this->loggerMock
            ->expects($this->once())
            ->method('debug')
            ->with(json_encode(['error' => "imagecreatefromstring(): Data is not in a recognized format"]));

        $this->controller->execute();
    }

    public function testExecuteWithValidBase64()
    {
        $image = imagecreatetruecolor(100, 100);
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        $base64Image = base64_encode($imageData);

        $this->requestMock->method('getParam')->with('data')->willReturn($base64Image);

        $this->controller->execute();

        $this->assertNotEmpty($this->responseBody);
        $this->assertStringContainsString("\x89PNG", $this->responseBody); 
    }
}