class TextPreprocessor:
    def __init__(self, text):
        self.text = text

    def preprocess_text(self):
        """Preprocess the text by removing consecutive spaces and newlines."""
        # Replace consecutive spaces with a single space
        processed_text = ' '.join(self.text.split())

        # Replace consecutive newlines with a single newline
        processed_text = '\n'.join(filter(None, self.text.split('\n')))

        return processed_text
