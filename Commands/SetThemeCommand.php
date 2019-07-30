<?php


namespace SetThemeCommand\Commands;


use Shopware\Commands\ShopwareCommand;
use Shopware\Components\Theme\Installer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetThemeCommand extends ShopwareCommand
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    protected function configure()
    {
        $this
            ->setName('wdy:theme:set')
            ->setDescription('Sets a theme.')
            ->addArgument(
                'theme',
                InputArgument::REQUIRED,
                'Name of the theme to be set.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Installer $themeInstaller */
        $themeInstaller = $this->container->get('theme_installer');
        $themeInstaller->synchronize();

        $this->conn = $this->container->get('dbal_connection');

        $name = $input->getArgument('theme');
        $templateId = $this->getThemeId($name);
        $this->updateDefaultTemplateId($templateId);

        $output->writeln('Theme ' . $name . ' set.');
    }

    private function getThemeId($name)
    {
        $statement = $this->conn->prepare('SELECT id FROM s_core_templates WHERE template LIKE ?');
        $statement->execute([$name]);
        $templateId = $statement->fetchColumn();

        if (!$templateId) {
            throw new \RuntimeException('Could not get id for default template');
        }

        return (int)$templateId;
    }

    private function updateDefaultTemplateId($templateId)
    {
        $sql = <<<'EOF'
UPDATE s_core_shops
SET template_id = :templateId,
    document_template_id = :templateId
WHERE `default` = 1
EOF;

        $statement = $this->conn->prepare($sql);
        $statement->execute(['templateId' => $templateId]);
    }
}