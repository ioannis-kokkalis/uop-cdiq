import fitz  # PyMuPDF
from docx import Document


class TextExtractor:
    def __init__(self, file_path):
        self.file_path = file_path

    def extract_text(self):
        """Extract text from PDF, DOC, or DOCX file."""
        if self.file_path.endswith('.pdf'):
            # Extract text from PDF
            text = self.extract_text_from_pdf()
        elif self.file_path.endswith('.docx'):
            # Extract text from DOCX
            text = self.extract_text_from_docx()
        else:
            raise ValueError("Unsupported file format")
        
        return text

    def extract_text_from_pdf(self):
        """Extract text from PDF file."""
        text = ""
        with fitz.open(self.file_path) as pdf_file:
            for page in pdf_file:
                text += page.get_text()
        return text

    def extract_text_from_docx(self):
        """Extract text from DOCX file."""
        doc = Document(self.file_path)
        text = ""
        for paragraph in doc.paragraphs:
            text += paragraph.text + "\n"
        return text


