<?php

namespace App\Console\Commands;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Console\Command;

class AbrirFlow extends Command
{
    protected $signature = 'abrir:flow';
    protected $description = 'Abre o Flow e clica no botão "Novo projeto"';

    public function handle()
    {
        $this->info('🚀 Iniciando navegador com perfil do usuário...');

        $host = 'http://localhost:9515';

        $options = new ChromeOptions();

        // ✅ Usa o Chrome da pasta baixada (versão 137 compatível)
        $options->setBinary('C:/xampp/htdocs/dashboard/chatgpt/chrome-win64/chrome.exe');

        $options->addArguments([
            '--user-data-dir=C:/Users/Usuario/AppData/Local/Google/Chrome/User Data',
            '--profile-directory=Profile 3',
            '--remote-debugging-port=9222',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
        ]);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        $driver = RemoteWebDriver::create($host, $capabilities);

        $driver->get('https://labs.google/fx/pt/tools/flow');

        $this->info('⏳ Aguardando a página carregar...');
        sleep(5);

        try {
            $botao = $driver->findElement(
                WebDriverBy::xpath("//button[contains(., 'Novo projeto')]")
            );
            $botao->click();
            $this->info('✅ Botão "Novo projeto" clicado com sucesso!');
        } catch (\Exception $e) {
            $this->error('❌ Erro ao clicar no botão: ' . $e->getMessage());
        }

        // Deixe o navegador aberto
        // $driver->quit();
    }
}
