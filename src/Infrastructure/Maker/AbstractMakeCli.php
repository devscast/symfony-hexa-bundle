<?php

declare(strict_types=1);

namespace Devscast\Bundle\HexaBundle\Infrastructure\Maker;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Twig\Environment;
use Twig\TwigFunction;

/**
 * class AbstractMakeCommand.
 *
 * @author bernard-ng <bernard@devscast.tech>
 */
abstract class AbstractMakeCli extends Command
{
    protected SymfonyStyle $io;

    public function __construct(
        private readonly Environment $twig,
        protected readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_OPTIONAL, 'force file creation', false);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function createRawFile(string $name, bool $directoryOnly = true): void
    {
        $filename = "{$this->projectDir}/{$name}";
        $directory = dirname($filename);

        if (! file_exists($directory)) {
            mkdir($directory, 0777, true);
            $this->io->text("Created directory {$directory}");
        }

        if (! $directoryOnly) {
            if (file_exists($filename)) {
                $this->io->text(sprintf('Skipped : File %s already exists', $filename));
            } else {
                file_put_contents($filename, '');
                $this->io->text("Created file {$filename}");
            }
        }
    }

    protected function addTwigFunction(string $name, callable $fn): void
    {
        $this->twig->addFunction(new TwigFunction($name, $fn));
    }

    protected function findFiles(string $path, string $suffix = ''): array
    {
        $directory = scandir(directory: $path);

        if ($directory === false) {
            return [];
        }

        return array_map(
            callback: fn ($file) => str_replace(
                search: $suffix,
                replace: '',
                subject: $file
            ),
            array: array_diff(
                $directory,
                ['..', '.']
            )
        );
    }

    protected function createFile(string $template, array $params, string $output, bool $force = false): void
    {
        $content = $this->twig->render("@DevscastHexa/maker/{$template}.twig", $params);
        $filename = "{$this->projectDir}/{$output}";
        $directory = dirname($filename);

        if (! file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        if (file_exists($filename) && ! $force) {
            $this->io->warning(sprintf('Skipped : File %s already exists', $filename));
        } else {
            file_put_contents($filename, $content);
        }
    }

    /**
     * Demande à l'utilisateur de choisir une class parmi une liste correspondant au motif.
     */
    protected function askClass(InputInterface $input, string $name, string $pattern): void
    {
        if ($input->getArgument($name) === null) {
            // On construit la liste utilisée pour l'autocomplétion
            $classes = [];
            $paths = explode('/', $pattern);

            if (count($paths) === 1) {
                $directory = "{$this->projectDir}/src";
            } else {
                $directory = sprintf("{$this->projectDir}/src/%s", join('/', array_slice($paths, 0, -1)));
                $pattern = join('/', array_slice($paths, -1));
            }

            $files = (new Finder())->in($directory)->name($pattern . '.php')->files();

            /** @var SplFileInfo $file */
            foreach ($files as $file) {
                $filename = str_replace('.php', '', $file->getBasename());
                $classes[$filename] = $file->getPathname();
            }

            // On pose à l'utilisateur la question
            $q = new Question($name);
            $q->setAutocompleterValues(array_keys($classes));
            $input->setArgument($name, $this->io->askQuestion($q));
        }
    }

    /**
     * Demande à l'utilisateur de choisir un domaine.
     */
    protected function askDomain(InputInterface $input): void
    {
        if ($input->getArgument('domain') === null) {
            // On construit la liste utilisée pour l'autocomplétion
            $domains = [];
            $files = (new Finder())->in("{$this->projectDir}/src/Domain")->depth(0)->directories();
            /** @var SplFileInfo $file */
            foreach ($files as $file) {
                $domains[] = $file->getBasename();
            }

            // On pose à l'utilisateur la question
            $q = new Question('Choose a Bounded Context');
            $q->setAutocompleterValues($domains);
            $input->setArgument('domain', $this->io->askQuestion($q));
        }
    }

    protected function askArgument(InputInterface $input, string $name, bool $hidden = false): void
    {
        /** @var string $value */
        $value = $input->getArgument($name);
        if ($value !== '') {
            $this->io->text(sprintf(' > <info>%s</info>: %s', $name, $value));
        } else {
            $value = match ($hidden) {
                false => $this->io->ask($name),
                default => $this->io->askHidden($name)
            };
            $input->setArgument($name, $value);
        }
    }

    /**
     * @template T of object
     * @param class-string<T> $fqcn
     *
     * @throws \ReflectionException
     */
    protected function getClassProperties(string $fqcn, array $ignore = []): array
    {
        $classFqcn = new \ReflectionClass($fqcn);
        $classProperties = $classFqcn->getProperties();
        $properties = [];

        foreach ($classProperties as $property) {
            if (! in_array($property->getName(), $ignore, true)) {
                /** @var \ReflectionNamedType $type */
                $type = $property->getType();

                $properties[] = [
                    'name' => $property->getName(),
                    'type' => $type->getName(),
                    'native' => $type->isBuiltin(),
                ];
            }
        }

        return $properties;
    }
}
