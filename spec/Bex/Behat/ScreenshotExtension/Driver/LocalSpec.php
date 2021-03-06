<?php

namespace spec\Bex\Behat\ScreenshotExtension\Driver;

use Bex\Behat\ScreenshotExtension\Driver\Local;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Prophecy\Prophet;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class LocalSpec extends ObjectBehavior
{
    function let(Filesystem $filesystem, Finder $finder, \finfo $fileInfo)
    {
        $this->beConstructedWith($filesystem, $finder, $fileInfo);

        $this->initializeFinderStub($finder);
        $this->initializeFileInfoStub($fileInfo);
    }

    function it_cleans_up_screenshot_directory_when_switch_is_on(
        Filesystem $filesystem, Finder $finder, ContainerBuilder $container
    ) {
        $config = $this->getDefaultConfig();
        $config[Local::CONFIG_PARAM_CLEAR_SCREENSHOT_DIRECTORY] = true;
        $images = ['a.png', 'b.png'];
        $files = [];
        $prophet = new Prophet;

        foreach ($images as $image) {
            $splFileInfo = $prophet->prophesize('\SplFileInfo');
            $splFileInfo->getRealPath()->willReturn($image);
            $files[] = $splFileInfo;
        }
        $finder->in(Argument::type('string'))->willReturn($files);

        $filesystem->remove($images)->shouldBeCalled();

        $this->load($container, $config);
    }

    function it_does_not_clean_up_screenshot_directory_when_switch_is_off(
        Filesystem $filesystem, Finder $finder, ContainerBuilder $container
    ) {
        $config = $this->getDefaultConfig();
        $config[Local::CONFIG_PARAM_CLEAR_SCREENSHOT_DIRECTORY] = false;
        $images = ['a.png', 'b.png'];
        $files = [];
        $prophet = new Prophet;

        foreach ($images as $image) {
            $splFileInfo = $prophet->prophesize('\SplFileInfo');
            $splFileInfo->getRealPath()->willReturn($image);
            $files[] = $splFileInfo;
        }
        $finder->in(Argument::type('string'))->willReturn($files);

        $filesystem->remove(Argument::any())->shouldNotBeCalled();

        $this->load($container, $config);
    }

    function it_removes_only_image_files(
        Filesystem $filesystem, Finder $finder, ContainerBuilder $container, \finfo $fileInfo
    ) {
        $config = $this->getDefaultConfig();
        $config[Local::CONFIG_PARAM_CLEAR_SCREENSHOT_DIRECTORY] = true;
        $images = ['a.png', 'b.png'];
        $nonImages = ['c.png', 'd.txt'];
        $files = [];
        $prophet = new Prophet;

        foreach (array_merge($images, $nonImages) as $filename) {
            $splFileInfo = $prophet->prophesize('\SplFileInfo');
            $splFileInfo->getRealPath()->willReturn($filename);
            $files[] = $splFileInfo;
        }
        $finder->in(Argument::type('string'))->willReturn($files);

        foreach ($nonImages as $filename) {
            $fileInfo->file($filename, FILEINFO_MIME_TYPE)->willReturn('plain/text');
        }

        $filesystem->remove($images)->shouldBeCalled();

        $this->load($container, $config);
    }

    private function initializeFinderStub(Finder $finder)
    {
        $finder->files()->willReturn($finder);
    }

    private function initializeFileInfoStub(\finfo $fileInfo)
    {
        $fileInfo->file(Argument::type('string'), FILEINFO_MIME_TYPE)->willReturn('image/png');
    }

    /**
     * @return array
     */
    private function getDefaultConfig()
    {
        $config = [
            Local::CONFIG_PARAM_SCREENSHOT_DIRECTORY       => '/path/to/screenshots/',
            Local::CONFIG_PARAM_CLEAR_SCREENSHOT_DIRECTORY => false,
        ];
        return $config;
    }
}
