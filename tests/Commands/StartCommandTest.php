<?php

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

function temporaryContentPath(string $suffix): string
{
    return sys_get_temp_dir() . '/fusion-start-' . $suffix . '-' . uniqid();
}

test('Start command inspects an existing content directory', function () {
    $contentDirectory = temporaryContentPath('existing');
    File::ensureDirectoryExists($contentDirectory . '/article');
    copyTestDataFile('content/article/article-1.md', $contentDirectory . '/article/hello.md');

    try {
        $this->artisan('fusion:start', ['--dir' => $contentDirectory])
            ->expectsOutputToContain('Content type')
            ->expectsOutputToContain('Markdown files')
            ->expectsOutputToContain('fusion:sync-model')
            ->assertExitCode(Command::SUCCESS);
    } finally {
        File::deleteDirectory($contentDirectory);
    }
});

test('Start command creates a missing content directory', function () {
    $contentDirectory = temporaryContentPath('create');

    try {
        $this->artisan('fusion:start', ['--dir' => $contentDirectory])
            ->expectsConfirmation("The content directory doesn't exist. Create it?", 'yes')
            ->expectsOutputToContain('Content directory created.')
            ->assertExitCode(Command::SUCCESS);

        expect($contentDirectory)->toBeDirectory();
    } finally {
        File::deleteDirectory($contentDirectory);
    }
});

test('Start command leaves a missing content directory unchanged', function () {
    $contentDirectory = temporaryContentPath('decline');

    $this->artisan('fusion:start', ['--dir' => $contentDirectory])
        ->expectsConfirmation("The content directory doesn't exist. Create it?", 'no')
        ->expectsOutputToContain('No directory was created.')
        ->assertExitCode(Command::SUCCESS);

    expect($contentDirectory)->not->toBeDirectory();
});

test('Start command reports a directory creation failure', function () {
    $parentFile = temporaryContentPath('failure');
    $contentDirectory = $parentFile . '/content';
    File::put($parentFile, 'not a directory');

    try {
        $this->artisan('fusion:start', ['--dir' => $contentDirectory])
            ->expectsConfirmation("The content directory doesn't exist. Create it?", 'yes')
            ->expectsOutputToContain('Unable to create the content directory')
            ->assertExitCode(Command::FAILURE);
    } finally {
        File::delete($parentFile);
    }
});
