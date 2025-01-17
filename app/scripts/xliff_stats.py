#! /usr/bin/env python
# -*- coding: utf-8 -*-

import argparse
import json
import sys
from xml.dom import minidom


def analyze_file(file_path, string_list, untranslated_strings):
    # This function parses a XLIFF file
    #
    # file_path: path to the XLIFF file to analyze
    # string_list: string IDs are stored in the form of fileunit:stringid
    # untranslated_strings: untranslated strings
    #
    # Returns a JSON record with stats about translations.
    #

    identical = 0
    missing = 0
    total = 0
    translated = 0
    untranslated = 0
    errors = []

    try:
        xmldoc = minidom.parse(file_path)
        trans_units = xmldoc.getElementsByTagName('trans-unit')
        for trans_unit in trans_units:
            source = trans_unit.getElementsByTagName('source')
            target = trans_unit.getElementsByTagName('target')

            file_element_name = trans_unit.parentNode.parentNode.attributes[
                'original'].value
            # Store the string ID
            string_id = '%s:%s' % \
                        (file_element_name, trans_unit.attributes['id'].value)
            string_list.append(string_id)

            # Check if we have at least one source
            if not source:
                error_msg = u'Trans unit “%s” in file ”%s” is missing a <source> element' \
                            % (trans_unit.attributes['id'].value, file_element_name)
                errors.append(error_msg)
                continue

            # Check if there are multiple source/target elements
            if len(source) > 1:
                # Exclude elements in alt-trans nodes
                source_count = 0
                for source_element in source:
                    if source_element.parentNode.tagName != 'alt-trans':
                        source_count += 1
                if source_count > 1:
                    error_msg = u'Trans unit “%s” in file ”%s” has multiple <source> elements' \
                                % (trans_unit.attributes['id'].value, file_element_name)
                    errors.append(error_msg)
            if len(target) > 1:
                target_count = 0
                for target_element in target:
                    if target_element.parentNode.tagName != 'alt-trans':
                        target_count += 1
                if target_count > 1:
                    error_msg = u'Trans unit “%s” in file ”%s” has multiple <target> elements' \
                                % (trans_unit.attributes['id'].value, file_element_name)
                    errors.append(error_msg)

            # Compare strings
            try:
                source_string = source[0].firstChild.data
            except:
                error_msg = u'Trans unit “%s” in file ”%s” has a malformed or empty <source> element' \
                            % (trans_unit.attributes['id'].value, file_element_name)
                errors.append(error_msg)
                continue
            if target:
                try:
                    target_string = target[0].firstChild.data
                except:
                    target_string = ''
                translated += 1
                if source_string == target_string:
                    identical += 1
            else:
                untranslated_strings.append(string_id)
                untranslated += 1

        # If we have translations, check if the first file is missing a
        # target-language
        if translated + identical > 1:
            file_elements = xmldoc.getElementsByTagName('file')
            if len(file_elements) > 0:
                file_element = file_elements[0]
                if 'target-language' not in file_element.attributes.keys():
                    error_msg = u'File “%s” is missing target-language attribute' \
                                % file_element.attributes['original'].value
                    errors.append(error_msg)
    except Exception as e:
        print e
        sys.exit(1)

    total = translated + untranslated
    file_stats = {
        "errors": ' - '.join(errors),
        "identical": identical,
        "total": total,
        "translated": translated,
        "untranslated": untranslated
    }

    return file_stats


def diff(a, b):
    b = set(b)
    return [aa for aa in a if aa not in b]


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('reference_file', help='Path to reference XLIFF file')
    parser.add_argument('locale_file', help='Path to localized XLIFF file')
    args = parser.parse_args()

    reference_strings = []
    reference_stats = analyze_file(
        args.reference_file,
        reference_strings,
        []
    )

    locale_strings = []
    untranslated_strings = []
    locale_stats = analyze_file(
        args.locale_file,
        locale_strings,
        untranslated_strings
    )

    # Check missing/obsolete strings
    missing_strings = diff(reference_strings, locale_strings)
    obsolete_strings = diff(locale_strings, reference_strings)
    locale_stats['missing'] = len(missing_strings)
    locale_stats['obsolete'] = len(obsolete_strings)
    locale_stats['missing_strings'] = missing_strings
    locale_stats['obsolete_strings'] = obsolete_strings
    locale_stats['untranslated_strings'] = untranslated_strings

    print json.dumps(locale_stats)

if __name__ == '__main__':
    main()
