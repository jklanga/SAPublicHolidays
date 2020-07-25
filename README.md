## SA Public Holidays
This repository fetches South African public holidays from http://kayaposoft.com/enrico for a given year and displays them on a page.

## Instructions

```bash
cp .env.example .env
```
Update the database settings in the .env file

Run the migrations
```bash
php artisan migrate
```

Use the following command to fetch the SA public holidays.
```bash
php artisan SAHolidays:fetch 2020 # Fetching SA public holidays for year 2020.
```
You can also fetch and list the holidays on the browser. There is a link to download the holidays list as a PDF.
