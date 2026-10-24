<?php

namespace Base\Music\Command;

use Base\Music\Entity\Video;
use Base\Music\Service\PosterPicker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Gives the videos without a poster the best of their own frames (Service\PosterPicker): every video
 * again with --all, one with --video. A poster uploaded by hand is kept unless --all.
 */
#[AsCommand(name: 'music:posters', description: 'Chooses a poster for the videos among their own frames (exposure, sharpness, the musician and the instrument), no AI.')]
class PostersCommand extends Command
{
    public function __construct(
        private readonly PosterPicker $picker,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('all', null, InputOption::VALUE_NONE, 'Every video again, a poster set by hand included')
            ->addOption('video', null, InputOption::VALUE_REQUIRED, 'One video, by its id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $repository = $this->entityManager->getRepository(Video::class);
        if (null !== $id = $input->getOption('video')) {
            $video = $repository->find((int) $id);
            if (null === $video) {
                $io->error(sprintf('No video %s.', $id));

                return Command::FAILURE;
            }
            $videos = [$video];
        } else {
            $videos = array_filter($repository->findAll(), static fn (Video $video): bool => $input->getOption('all') || !$video->getPoster());
        }

        $done = $skipped = 0;
        foreach ($videos as $video) {
            if ($this->picker->pick($video)) {
                ++$done;
                $io->writeln(sprintf(' <info>✓</info> #%d %s', $video->getId(), $video->getTitle()), OutputInterface::VERBOSITY_VERBOSE);
            } else {
                ++$skipped;
                $io->writeln(sprintf(' <comment>-</comment> #%d %s', $video->getId(), $video->getTitle()), OutputInterface::VERBOSITY_VERBOSE);
            }
        }
        $this->entityManager->flush();

        $io->success(sprintf('%d poster(s) chosen, %d video(s) skipped (no frame could be had).', $done, $skipped));

        return Command::SUCCESS;
    }
}
