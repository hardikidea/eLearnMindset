#!/usr/bin/env python3
from pathlib import Path
import argparse, csv, shutil
PACK = Path(__file__).resolve().parents[1]

def transform_value(value, from_year, to_year):
    return value.replace(from_year, to_year).replace(from_year.replace('-', '_'), to_year.replace('-', '_')).replace(from_year.split('-')[0], to_year.split('-')[0]).replace(from_year.split('-')[0][-2:], to_year.split('-')[0][-2:])

def transform_csv(src, dst, from_year, to_year, empty=False):
    dst.parent.mkdir(parents=True, exist_ok=True)
    with src.open(newline='') as f:
        rows = list(csv.reader(f))
    if not rows:
        return
    header, data = rows[0], rows[1:]
    if empty:
        data = []
    else:
        data = [[transform_value(cell, from_year, to_year) for cell in row] for row in data]
    with dst.open('w', newline='') as f:
        w = csv.writer(f); w.writerow(header); w.writerows(data)

def main():
    p = argparse.ArgumentParser()
    p.add_argument('--from-year', required=True)
    p.add_argument('--to-year', required=True)
    args = p.parse_args()
    src = PACK / 'years' / args.from_year
    dst = PACK / 'years' / args.to_year
    if not src.exists():
        raise SystemExit(f'Missing source year: {src}')
    if dst.exists():
        raise SystemExit(f'Target year already exists: {dst}')
    dst.mkdir(parents=True)
    for file in src.glob('*.csv'):
        empty = file.name in {'cohort_members.csv','academic_history.csv','promotion_actions.csv'} or file.name.startswith('promotion_plan_to_')
        target_name = file.name.replace(args.from_year, args.to_year)
        transform_csv(file, dst / target_name, args.from_year, args.to_year, empty=empty)
    print(f'Generated {dst}')
if __name__ == '__main__':
    main()
