# Configuration file for the Sphinx documentation builder.

project = 'CodeIgniter Job Scheduler'
copyright = '2025'
author = 'daycry'

extensions = [
    'myst_parser',
]

source_suffix = {
    '.rst': 'restructuredtext',
    '.md': 'markdown',
}

master_doc = 'index'

exclude_patterns = ['_build', 'Thumbs.db', '.DS_Store']

html_theme = 'furo'
