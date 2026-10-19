<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('titre', 'Sunu Santé')</title>
        <style>
            @media only screen and (max-width: 620px) {
                .sunu-container {
                    width: 100% !important;
                    max-width: 100% !important;
                }
                .sunu-padding {
                    padding-left: 20px !important;
                    padding-right: 20px !important;
                }
                .sunu-title {
                    font-size: 21px !important;
                }
            }
            @media (prefers-color-scheme: dark) {
                .sunu-body {
                    background-color: #F0FDFA !important;
                }
                .sunu-container {
                    background-color: #FFFFFF !important;
                }
                .sunu-text {
                    color: #0F172A !important;
                }
            }
        </style>
    </head>
    <body class="sunu-body" style="margin: 0; padding: 0; background-color: #F0FDFA; font-family: Arial, Helvetica, sans-serif; -webkit-text-size-adjust: 100%; color: #0F172A;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#F0FDFA" style="background-color: #F0FDFA;">
            <tr>
                <td align="center" style="padding: 24px 12px;">
                    <table role="presentation" class="sunu-container" width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#FFFFFF" style="width: 600px; max-width: 600px; background-color: #FFFFFF; border: 1px solid #CCFBF1; border-radius: 12px; overflow: hidden;">
                        <tr>
                            <td class="sunu-padding" style="padding: 24px 32px 16px; background-color: #FFFFFF; border-bottom: 3px solid #0F766E;">
                                <img src="{{ $message->embed(public_path('images/logo-sunu-sante.png')) }}" width="180" height="76" alt="Sunu Santé" style="display: block; width: 180px; height: 76px; border: 0;">
                            </td>
                        </tr>
                        <tr>
                            <td class="sunu-padding sunu-text" style="padding: 28px 32px 8px; font-size: 16px; line-height: 1.55; color: #0F172A;">
                                <h1 class="sunu-title" style="margin: 0 0 20px; font-size: 24px; font-weight: bold; color: #115E59;">@yield('titreH1')</h1>
                                @yield('contenu')
                                <p style="margin: 24px 0 0;">Merci de votre confiance.</p>
                            </td>
                        </tr>
                        <tr>
                            <td class="sunu-padding" style="padding: 16px 32px 24px; background-color: #F0FDFA; border-top: 1px solid #99F6E4; text-align: center; font-size: 13px; line-height: 1.6; color: #115E59;">
                                L'équipe Sunu Santé<br>
                                Hôpital Fann, Dakar
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
