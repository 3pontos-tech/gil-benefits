<?php

use App\Enums\AvailableTagsEnum;
use App\Models\Consultant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $consultants = [
        [
            'name' => 'Daniel Reis',
            'slug' => 'danielhe4rt',
            'phone' => '+551112312312',
            'email' => 'hey@danielheart.dev',
            'short_description' => 'Um não consultor com zero anos de proficiência',
            'biography' => 'Daniel é um desenvolvedor de software com mais de 10 anos de experiência em várias tecnologias e setores. Ele é apaixonado por criar soluções inovadoras que impulsionam o sucesso dos negócios e melhoram a vida das pessoas. Com uma sólida formação em ciência da computação e um histórico comprovado de entrega de projetos bem-sucedidos, Daniel é um consultor confiável para empresas que buscam excelência técnica e estratégica.',
            'readme' => <<<'MARKDOWN'
            # Como trabalhar comigo?
            
            Não seja otário e: 
            
            Some of my contributions there involves:

            - Writing tutorials and demos for async drivers and messaging system    
            - Engaging with the open-source community through live streams and content
            - Supporting educational initiatives to onboard developers to distributed databases
            - Representing ScyllaDB in developer-focused spaces with transparency and approachability
            MARKDOWN,
        ],
    ];

    public function up(): void
    {
        DB::table('consultants')->truncate();
        foreach ($this->consultants as $consultant) {

            $consultant['socials_urls'] = json_encode([
                'linkedin' => 'https://www.linkedin.com/in/',
                'instagram' => 'https://www.instagram.com/',
                'facebook' => 'https://www.facebook.com/',
                'twitter' => 'https://www.twitter.com/',
                'youtube' => 'https://www.youtube.com/',
                'website' => 'https://www.website.com/',
            ]);

            $userId = DB::table('consultants')->insertGetId($consultant);

            $consultant = Consultant::query()->find($userId);
            foreach (AvailableTagsEnum::cases() as $case) {
                $consultant->attachTags($case->getDefault(), $case->value);
            }

            $consultant->addMediaFromUrl('https://github.com/danielhe4rt.png')
                ->preservingOriginal()
                ->toMediaCollection('avatars');
        }
    }
};
