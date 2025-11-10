# Automated XOPP 2 PDF conversion

> Please note:
> This app is based on [Automated PDF conversion](https://github.com/nextcloud/workflow_pdf_converter). Only minimal changes have been made to support .xoj and .xopp files instead. Therefore: Many thanks to the project maintainers of [Automated PDF conversion](https://github.com/nextcloud/workflow_pdf_converter)!

## To whom it may concern

This app can automatically convert .xopp and .xoj files into PDFs. This allows you to create Xournal(++) files on your PC, and if they are synced to Nextcloud, you will always be able to open the PDF version — for example, when you're out and about.

## Installation

Install Xournal++ in the back end of your server.

    apt update
    apt install -y xournalpp

Next, install this app in your Nextcloud Apps Page
Finally, set up the workflow. Create a filter "File name" "matches" and put the following into the text field:

    /^.+\.(xoj|xopp)$/i

This will support Xournal and Xournal++ files.

## Limitations

As [already described](https://github.com/nextcloud/workflow_pdf_converter?tab=readme-ov-file#limitations) in the original app, encrypted files are not supported.

## Thanks

Thanks again to Xournal, Xournal++, Nextcloud and Automated PDF conversion for all their hard work, which makes my life so much easier!


