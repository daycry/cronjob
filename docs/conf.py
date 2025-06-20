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

exclude_patterns = ['_build', 'Thumbs.db', '.DS_Store', 'index.md', 'home.rst']

html_theme = 'sphinx_rtd_theme'
html_logo = 'images/logo.svg'
