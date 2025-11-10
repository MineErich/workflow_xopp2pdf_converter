<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WorkflowXoppToPdfConverter\Listener;

use OCA\WorkflowXoppToPdfConverter\Operation;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;
use Psr\Container\ContainerInterface;

/**
 * @template-implements IEventListener<RegisterOperationsEvent>
 */
class RegisterFlowOperationsListener implements IEventListener {
	private ContainerInterface $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	public function handle(Event $event): void {
		if (!$event instanceof RegisterOperationsEvent) {
			return;
		}
		$event->registerOperation($this->container->get(Operation::class));
		Util::addScript('workflow_xopp2pdf_converter', 'workflow_xopp2pdf_converter-main');
	}
}
