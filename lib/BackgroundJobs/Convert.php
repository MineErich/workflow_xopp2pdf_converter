<?php



namespace OCA\WorkflowXoppToPdfConverter\BackgroundJobs;

use Exception;
use OC\Files\Filesystem;
use OC\Files\View;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\ITempManager;
use Psr\Log\LoggerInterface;

class Convert extends QueuedJob {
	protected IConfig $config;
	protected ITempManager $tempManager;
	protected LoggerInterface $logger;
	private IRootFolder $rootFolder;

	public function __construct(
		IConfig $config,
		ITempManager $tempManager,
		LoggerInterface $logger,
		IRootFolder $rootFolder,
		ITimeFactory $timeFactory,
	) {
		parent::__construct($timeFactory);
		$this->config = $config;
		$this->tempManager = $tempManager;
		$this->logger = $logger;
		$this->rootFolder = $rootFolder;
	}

	/**
	 * @param mixed $argument
	 * @throws Exception
	 * @throws InvalidPathException
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 */
	protected function run($argument) {
		$command = $this->getCommand();
		if ($command === null) {
			$this->logger->error('Can not find xournalpp path. Please make sure to configure "preview_xournalpp_path" in your config file.');
		}

		$path = (string)$argument['path'];
		$originalFileMode = (string)$argument['originalFileMode'];
		$targetPdfMode = (string)$argument['targetPdfMode'];

		$pathSegments = explode('/', $path, 4);
		$dir = dirname($path);
		$file = basename($path);

		Filesystem::init($pathSegments[1], '/' . $pathSegments[1] . '/files');
		try {
			$node = $this->rootFolder->get($path);
		} catch (NotFoundException $e) {
			return;
		}
		$view = new View($dir);

		$tmpPath = $view->toTmpFile($file);
		$tmpDir = $this->tempManager->getTempBaseDir();

		// get filename without ending
		$baseName = pathinfo($file, PATHINFO_FILENAME);
		$newFileName = $baseName . '.pdf';
		$newTmpPath = $tmpDir . '/' . $newFileName;

		$exec = escapeshellcmd($command)
			. ' --create-pdf=' . escapeshellarg($newTmpPath)
			. ' ' . escapeshellarg($tmpPath);

		$exitCode = 0;
		exec($exec, $out, $exitCode);
		if ($exitCode !== 0) {
			$this->logger->error('could not convert {file}, reason: {out}', 
			[
				'app' => 'workflow_xopp2pdf_converter',
				'file' => $node->getPath(),
				'out' => $out
			]
		);
			return;
		}

		// handle conflicts, if file already exists
		$folder = $node->getParent();
		$index = 0;
		while ($targetPdfMode === 'preserve' && $folder->nodeExists($newFileName)) {
			$index++;
			$newFileName = $baseName . ' (' . $index . ').pdf';
		}

		// write converted file back to nextcloud
		$view->fromTmpFile($newTmpPath, $newFileName);

		// delete original, if wished
		if ($originalFileMode === 'delete') {
			try {
				$node->delete();
			} catch (\Exception $e) {
				$this->logger->warning('could not delete original file {file}: {msg}', [
					'app' => 'workflow_xopp2pdf_converter',
					'file' => $node->getPath(),
					'msg' => $e->getMessage(),
				]);
			}
		}
	}

	protected function getCommand(): ?string {
		$xournalppPath = $this->config->getSystemValue('preview_xournalpp_path', null);
		if (is_string($xournalppPath)) {
			return escapeshellcmd($xournalppPath);
		}

		$whichLibreOffice = shell_exec('command -v xournalpp');
		if (!empty($whichLibreOffice)) {
			return 'xournalpp';
		}

		return null;
	}
}
