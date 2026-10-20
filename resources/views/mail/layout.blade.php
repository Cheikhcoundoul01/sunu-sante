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
                .sunu-label {
                    display: block !important;
                    width: auto !important;
                    padding: 0 0 2px !important;
                }
                .sunu-value {
                    display: block !important;
                    padding: 0 0 10px !important;
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
                .sunu-title {
                    color: #115E59 !important;
                }
                .sunu-value {
                    color: #0F172A !important;
                }
            }
        </style>
    </head>
    <body class="sunu-body" style="margin: 0; padding: 0; background-color: #F0FDFA; font-family: Arial, Helvetica, sans-serif; -webkit-text-size-adjust: 100%; color: #0F172A;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#F0FDFA" style="background-color: #F0FDFA;">
            <tr>
                <td align="center" style="padding: 24px 12px;">
                    <table role="presentation" class="sunu-container" width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#FFFFFF" style="width: 600px; max-width: 600px; background-color: #FFFFFF; border: 1px solid #CCFBF1; border-radius: 14px; overflow: hidden;">
                        <tr>
                            <td height="6" style="height: 6px; line-height: 6px; font-size: 0; background-color: @yield('couleurAccent', '#0F766E');">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="sunu-padding" style="padding: 26px 32px 14px; background-color: #FFFFFF;">
                                <img src="{{ $message->embed(public_path('images/logo-sunu-sante.png')) }}" width="180" height="76" alt="Sunu Santé" style="display: block; width: 180px; height: 76px; border: 0;">
                            </td>
                        </tr>
                        <tr>
                            <td class="sunu-padding sunu-text" style="padding: 6px 32px 4px; font-size: 16px; line-height: 1.6; color: #0F172A;">
                                <h1 class="sunu-title" style="margin: 0 0 18px; font-size: 24px; line-height: 1.3; font-weight: bold; color: #115E59;">@yield('titreH1')</h1>
                                @yield('contenu')
                            </td>
                        </tr>
                        <tr>
                            <td class="sunu-padding sunu-text" style="padding: 6px 32px 26px; font-size: 15px; line-height: 1.6; color: #0F172A;">
                                <p style="margin: 0;">Merci de votre confiance.</p>
                            </td>
                        </tr>
                        <tr>
                            <td class="sunu-padding" style="padding: 16px 32px 24px; background-color: #F0FDFA; border-top: 1px solid #99F6E4; text-align: center; font-size: 13px; line-height: 1.7; color: #115E59;">
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
