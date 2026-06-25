<?php

declare(strict_types=1);

use Xjoc\NotificationCenter\Exceptions\MissingVariableException;
use Xjoc\NotificationCenter\Templates\TemplateRenderer;

beforeEach(function (): void {
    $this->renderer = new TemplateRenderer;
});

it('replaces escaped tokens with values', function (): void {
    /** @var TemplateRenderer $renderer */
    $renderer = $this->renderer;

    $result = $renderer->render('Hello {{ name }}', ['name' => 'Sam']);

    expect($result)->toBe('Hello Sam');
});

it('escapes html in escaped tokens when escaping is enabled', function (): void {
    /** @var TemplateRenderer $renderer */
    $renderer = $this->renderer;

    $result = $renderer->render('Hi {{ name }}', ['name' => '<b>x</b>'], escape: true);

    expect($result)->toBe('Hi '.e('<b>x</b>'));
    expect($result)->not->toContain('<b>');
});

it('does not escape escaped tokens when escaping is disabled', function (): void {
    /** @var TemplateRenderer $renderer */
    $renderer = $this->renderer;

    $result = $renderer->render('Hi {{ name }}', ['name' => '<b>x</b>'], escape: false);

    expect($result)->toBe('Hi <b>x</b>');
});

it('never escapes raw tokens regardless of the escape flag', function (): void {
    /** @var TemplateRenderer $renderer */
    $renderer = $this->renderer;

    $result = $renderer->render('Raw {!! html !!}', ['html' => '<i>y</i>'], escape: true);

    expect($result)->toBe('Raw <i>y</i>');
});

it('tolerates arbitrary whitespace inside token braces', function (): void {
    /** @var TemplateRenderer $renderer */
    $renderer = $this->renderer;

    $result = $renderer->render('A {{name}} B {{   name   }} C {!!name!!}', ['name' => 'Z']);

    expect($result)->toBe('A Z B Z C Z');
});

it('resolves dotted keys via Arr::get', function (): void {
    /** @var TemplateRenderer $renderer */
    $renderer = $this->renderer;

    $result = $renderer->render('Total {{ order.total }}', ['order' => ['total' => '42']]);

    expect($result)->toBe('Total 42');
});

it('processes raw tokens before escaped tokens', function (): void {
    /** @var TemplateRenderer $renderer */
    $renderer = $this->renderer;

    $result = $renderer->render('{!! raw !!} | {{ esc }}', ['raw' => '<a>', 'esc' => '<b>']);

    expect($result)->toBe('<a> | '.e('<b>'));
});

it('replaces missing keys with an empty string by default', function (): void {
    /** @var TemplateRenderer $renderer */
    $renderer = $this->renderer;

    $result = $renderer->render('Hello {{ name }}!', []);

    expect($result)->toBe('Hello !');
});

it('replaces missing keys with empty string when onMissing is empty', function (): void {
    /** @var TemplateRenderer $renderer */
    $renderer = $this->renderer;

    $result = $renderer->render('X {!! gone !!} Y {{ poof }} Z', [], escape: true, onMissing: 'empty');

    expect($result)->toBe('X  Y  Z');
});

it('throws MissingVariableException for a missing escaped key when onMissing is throw', function (): void {
    /** @var TemplateRenderer $renderer */
    $renderer = $this->renderer;

    expect(fn (): string => $renderer->render('Hi {{ name }}', [], escape: true, onMissing: 'throw'))
        ->toThrow(MissingVariableException::class, 'Missing template variable [name].');
});

it('throws MissingVariableException for a missing raw key when onMissing is throw', function (): void {
    /** @var TemplateRenderer $renderer */
    $renderer = $this->renderer;

    expect(fn (): string => $renderer->render('Hi {!! name !!}', [], escape: false, onMissing: 'throw'))
        ->toThrow(MissingVariableException::class, 'Missing template variable [name].');
});

it('casts non-string resolved values to string', function (): void {
    /** @var TemplateRenderer $renderer */
    $renderer = $this->renderer;

    $result = $renderer->render('Count {{ n }}', ['n' => 7]);

    expect($result)->toBe('Count 7');
});

it('treats a present null value as present and renders an empty string', function (): void {
    /** @var TemplateRenderer $renderer */
    $renderer = $this->renderer;

    $result = $renderer->render('V={{ v }}', ['v' => null], escape: true, onMissing: 'throw');

    expect($result)->toBe('V=');
});
